<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * File for abstract session driver.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;
defined('MOODLE_INTERNAL') || die();

/**
 * Abstract session driver.
 *
 * Handling all session and cookies related stuff.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class driver implements sessionable {
    protected $justloggedout;

    public function __construct() {
        global $CFG;

        if (NO_MOODLE_COOKIES) {
            // session not used at all
            $_SESSION = array();
            $_SESSION['SESSION'] = new \stdClass();
            $_SESSION['USER']    = new \stdClass();

        } else {
            $this->prepare_cookies();
            $this->init_session_storage();

            $newsession = empty($_COOKIE['MoodleSession'.$CFG->sessioncookie]);

            ini_set('session.use_trans_sid', '0');

            session_name('MoodleSession'.$CFG->sessioncookie);
            session_set_cookie_params(0, $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $CFG->cookiesecure, $CFG->cookiehttponly);
            session_start();
            if (!isset($_SESSION['SESSION'])) {
                $_SESSION['SESSION'] = new \stdClass();
                if (!$newsession and !$this->justloggedout) {
                    $_SESSION['SESSION']->has_timed_out = true;
                }
            }
            if (!isset($_SESSION['USER'])) {
                $_SESSION['USER'] = new \stdClass();
            }
        }

        $this->check_user_initialised();

        $this->check_security();
    }

    /**
     * Terminate current session
     * @return void
     */
    public function terminate_current() {
        global $CFG, $SESSION, $USER, $DB;

        try {
            $DB->delete_records('external_tokens', array('sid'=>session_id(), 'tokentype'=>EXTERNAL_TOKEN_EMBEDDED));
        } catch (\Exception $ignored) {
            // probably install/upgrade - ignore this problem
        }

        if (NO_MOODLE_COOKIES) {
            return;
        }

        // Initialize variable to pass-by-reference to headers_sent(&$file, &$line)
        $_SESSION = array();
        $_SESSION['SESSION'] = new \stdClass();
        $_SESSION['USER']    = new \stdClass();
        $_SESSION['USER']->id = 0;
        if (isset($CFG->mnet_localhost_id)) {
            $_SESSION['USER']->mnethostid = $CFG->mnet_localhost_id;
        }
        $SESSION = $_SESSION['SESSION']; // this may not work properly
        $USER    = $_SESSION['USER'];    // this may not work properly

        $file = null;
        $line = null;
        if (headers_sent($file, $line)) {
            error_log('Can not terminate session properly - headers were already sent in file: '.$file.' on line '.$line);
        }

        // now let's try to get a new session id and delete the old one
        $this->justloggedout = true;
        session_regenerate_id(true);
        $this->justloggedout = false;

        // write the new session
        session_write_close();
    }

    /**
     * No more changes in session expected.
     * Unblocks the sessions, other scripts may start executing in parallel.
     * @return void
     */
    public function write_close() {
        if (NO_MOODLE_COOKIES) {
            return;
        }

        session_write_close();
    }

    /**
     * Initialise $USER object, handles google access
     * and sets up not logged in user properly.
     *
     * @return void
     */
    protected function check_user_initialised() {
        global $CFG;

        if (isset($_SESSION['USER']->id)) {
            // already set up $USER
            return;
        }

        $user = null;

        if (!empty($CFG->opentogoogle) and !NO_MOODLE_COOKIES) {
            if (is_web_crawler()) {
                $user = guest_user();
            }
            if (!empty($CFG->guestloginbutton) and !$user and !empty($_SERVER['HTTP_REFERER'])) {
                // automaticaly log in users coming from search engine results
                if (strpos($_SERVER['HTTP_REFERER'], 'google') !== false ) {
                    $user = guest_user();
                } else if (strpos($_SERVER['HTTP_REFERER'], 'altavista') !== false ) {
                    $user = guest_user();
                }
            }
        }

        if (!$user) {
            $user = new \stdClass();
            $user->id = 0; // to enable proper function of $CFG->notloggedinroleid hack
            if (isset($CFG->mnet_localhost_id)) {
                $user->mnethostid = $CFG->mnet_localhost_id;
            } else {
                $user->mnethostid = 1;
            }
        }
        session_set_user($user);
    }

    /**
     * Does various session security checks
     * @global void
     */
    protected function check_security() {
        global $CFG;

        if (NO_MOODLE_COOKIES) {
            return;
        }

        if (!empty($_SESSION['USER']->id) and !empty($CFG->tracksessionip)) {
            /// Make sure current IP matches the one for this session
            $remoteaddr = getremoteaddr();

            if (empty($_SESSION['USER']->sessionip)) {
                $_SESSION['USER']->sessionip = $remoteaddr;
            }

            if ($_SESSION['USER']->sessionip != $remoteaddr) {
                // this is a security feature - terminate the session in case of any doubt
                $this->terminate_current();
                print_error('sessionipnomatch2', 'error');
            }
        }
    }

    /**
     * Prepare cookies and various system settings
     */
    protected function prepare_cookies() {
        global $CFG;

        if (!isset($CFG->cookiesecure) or (strpos($CFG->wwwroot, 'https://') !== 0 and empty($CFG->sslproxy))) {
            $CFG->cookiesecure = 0;
        }

        if (!isset($CFG->cookiehttponly)) {
            $CFG->cookiehttponly = 0;
        }

    /// Set sessioncookie and sessioncookiepath variable if it isn't already
        if (!isset($CFG->sessioncookie)) {
            $CFG->sessioncookie = '';
        }

        // make sure cookie domain makes sense for this wwwroot
        if (!isset($CFG->sessioncookiedomain)) {
            $CFG->sessioncookiedomain = '';
        } else if ($CFG->sessioncookiedomain !== '') {
            $host = parse_url($CFG->wwwroot, PHP_URL_HOST);
            if ($CFG->sessioncookiedomain !== $host) {
                if (substr($CFG->sessioncookiedomain, 0, 1) === '.') {
                    if (!preg_match('|^.*'.preg_quote($CFG->sessioncookiedomain, '|').'$|', $host)) {
                        // invalid domain - it must be end part of host
                        $CFG->sessioncookiedomain = '';
                    }
                } else {
                    if (!preg_match('|^.*\.'.preg_quote($CFG->sessioncookiedomain, '|').'$|', $host)) {
                        // invalid domain - it must be end part of host
                        $CFG->sessioncookiedomain = '';
                    }
                }
            }
        }

        // make sure the cookiepath is valid for this wwwroot or autodetect if not specified
        if (!isset($CFG->sessioncookiepath)) {
            $CFG->sessioncookiepath = '';
        }
        if ($CFG->sessioncookiepath !== '/') {
            $path = parse_url($CFG->wwwroot, PHP_URL_PATH).'/';
            if ($CFG->sessioncookiepath === '') {
                $CFG->sessioncookiepath = $path;
            } else {
                if (strpos($path, $CFG->sessioncookiepath) !== 0 or substr($CFG->sessioncookiepath, -1) !== '/') {
                    $CFG->sessioncookiepath = $path;
                }
            }
        }

        //discard session ID from POST, GET and globals to tighten security,
        //this is session fixation prevention
        unset(${'MoodleSession'.$CFG->sessioncookie});
        unset($_GET['MoodleSession'.$CFG->sessioncookie]);
        unset($_POST['MoodleSession'.$CFG->sessioncookie]);
        unset($_REQUEST['MoodleSession'.$CFG->sessioncookie]);

        //compatibility hack for Moodle Cron, cookies not deleted, but set to "deleted" - should not be needed with NO_MOODLE_COOKIES in cron.php now
        if (!empty($_COOKIE['MoodleSession'.$CFG->sessioncookie]) && $_COOKIE['MoodleSession'.$CFG->sessioncookie] == "deleted") {
            unset($_COOKIE['MoodleSession'.$CFG->sessioncookie]);
        }
    }

    /**
     * Init session storage.
     */
    protected abstract function init_session_storage();
}
