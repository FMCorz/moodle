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
 * @package    core
 * @subpackage session
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @copyright  2008, 2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!defined('SESSION_ACQUIRE_LOCK_TIMEOUT')) {
    /**
     * How much time to wait for session lock before displaying error (in seconds),
     * 2 minutes by default should be a reasonable time before telling users to wait and refresh browser.
     */
    define('SESSION_ACQUIRE_LOCK_TIMEOUT', 60*2);
}

/**
  * Factory method returning a sessionable object.
  * @return \core\session\sessionable
  */
function session_get_instance() {
    global $CFG, $DB;

    static $session = null;

    if (is_null($session)) {
        if (!defined('NO_MOODLE_COOKIES') or empty($DB)) {
            // Moodle was not initialised properly in lib/setup.php.
            $session = new \core\session\emergency();
            return $session;
        }

        if (empty($CFG->sessiontimeout)) {
            $CFG->sessiontimeout = 7200;
        }

        try {
            if (defined('SESSION_CUSTOM_CLASS')) {
                // This is a hook for webservices, key based login, etc.
                if (defined('SESSION_CUSTOM_FILE')) {
                    require_once($CFG->dirroot.SESSION_CUSTOM_FILE);
                }
                $session_class = SESSION_CUSTOM_CLASS;
                $session = new $session_class();

            } else if ((!isset($CFG->dbsessions) or $CFG->dbsessions) and $DB->session_lock_supported()) {
                // Default recommended session type.
                $session = new \core\session\driver_standard();

            } else {
                // Legacy limited file based storage - some features and auth plugins will not work, sorry.
                $session = new \core\session\driver_legacyfile();
            }
        } catch (Exception $ex) {
            // Prevent repeated inits.
            $session = new \core\session\emergency();
            throw $ex;
        }
    }

    return $session;
}


/**
 * Moodle session abstraction.
 *
 * @package    core
 * @subpackage session
 * @copyright  2008 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since 2.6, use \core\session\sessionnable instead.
 */
interface moodle_session extends \core\session\sessionable {
}


/**
 * Fallback session handler when standard session init fails.
 * This prevents repeated attempts to init faulty handler.
 *
 * @package    core
 * @subpackage session
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since 2.6, use \core\session\emergency instead.
 */
class emergency_session extends \core\session\emergency {
}


/**
 * Class handling all session and cookies related stuff.
 *
 * @package    core
 * @subpackage session
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since 2.6, use \core\session\driver instead.
 */
abstract class session_stub extends \core\session\driver {
}


/**
 * Legacy moodle sessions stored in files, not recommended any more.
 *
 * @package    core
 * @subpackage session
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since 2.6, use \core\session\driver_legacyfile instead.
 */
class legacy_file_session extends \core\session\driver_legacyfile {
}

/**
 * Memcached session handler
 *
 * @package    core
 * @subpackage session
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Mark Nielsen
 */
class memcached_session extends session_stub {
    protected function init_session_storage() {
        global $CFG;

        if (empty($CFG->session_save_path_memcached)) {
            throw new coding_exception('The $CFG->session_save_path_memcached is empty and must be configured for Memcached sessions');
        }
        ini_set('session.save_handler', 'memcached');
        ini_set('session.save_path', $CFG->session_save_path_memcached);
        ini_set('session.gc_maxlifetime', $CFG->sessiontimeout);
    }

    public function session_exists($sid) {
        // Note: $sid is session_id().
        $memcached = new Memcached();
        $memcached->addServers($this->connection_string_to_servers());
        $value = $memcached->get(ini_get('memcached.sess_prefix').$sid);
        $memcached->quit();

        if ($value !== false) {
            return true;
        }
        return false;
    }

    /**
     * Convert a connection string to an array of servers
     *
     * EG: Converts: "abc:123, xyz:789" to
     *
     *  array(
     *      array('abc', '123'),
     *      array('xyz', '789'),
     *  )
     *
     * @return array
     */
    protected function connection_string_to_servers() {
        global $CFG;

        $servers = array();
        $parts   = explode(',', $CFG->session_save_path_memcached);
        foreach ($parts as $part) {
            $part = trim($part);
            $pos  = strrpos($part, ':');
            if ($pos !== false) {
                $host = substr($part, 0, $pos);
                $port = substr($part, ($pos + 1));
            } else {
                $host = $part;
                $port = 11211;
            }
            $servers[] = array($host, $port);
        }
        return $servers;
    }
}

/**
 * Recommended moodle session storage.
 *
 * @package    core
 * @subpackage session
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since 2.6, use \core\session\driver_standard instead.
 */
class database_session extends \core\session\driver_standard {
}


/**
 * returns true if legacy session used.
 * @return bool true if legacy(==file) based session used
 */
function session_is_legacy() {
    global $CFG, $DB;
    return ((isset($CFG->dbsessions) and !$CFG->dbsessions) or !$DB->session_lock_supported());
}

/**
 * Terminates all sessions, auth hooks are not executed.
 * Useful in upgrade scripts.
 */
function session_kill_all() {
    global $CFG, $DB;

    // always check db table - custom session classes use sessions table
    try {
        $DB->delete_records('sessions');
    } catch (dml_exception $ignored) {
        // do not show any warnings - might be during upgrade/installation
    }

    if (session_is_legacy()) {
        $sessiondir = "$CFG->dataroot/sessions";
        if (is_dir($sessiondir)) {
            foreach (glob("$sessiondir/sess_*") as $filename) {
                @unlink($filename);
            }
        }
    }
}

/**
 * Mark session as accessed, prevents timeouts.
 * @param string $sid
 */
function session_touch($sid) {
    global $CFG, $DB;

    // always check db table - custom session classes use sessions table
    try {
        $sql = "UPDATE {sessions} SET timemodified=? WHERE sid=?";
        $params = array(time(), $sid);
        $DB->execute($sql, $params);
    } catch (dml_exception $ignored) {
        // do not show any warnings - might be during upgrade/installation
    }

    if (session_is_legacy()) {
        $sid = clean_param($sid, PARAM_FILE);
        $sessionfile = clean_param("$CFG->dataroot/sessions/sess_$sid", PARAM_FILE);
        if (file_exists($sessionfile)) {
            // if the file is locked it means that it will be updated anyway
            @touch($sessionfile);
        }
    }
}

/**
 * Terminates one sessions, auth hooks are not executed.
 *
 * @param string $sid session id
 */
function session_kill($sid) {
    global $CFG, $DB;

    // always check db table - custom session classes use sessions table
    try {
        $DB->delete_records('sessions', array('sid'=>$sid));
    } catch (dml_exception $ignored) {
        // do not show any warnings - might be during upgrade/installation
    }

    if (session_is_legacy()) {
        $sid = clean_param($sid, PARAM_FILE);
        $sessionfile = "$CFG->dataroot/sessions/sess_$sid";
        if (file_exists($sessionfile)) {
            @unlink($sessionfile);
        }
    }
}

/**
 * Terminates all sessions of one user, auth hooks are not executed.
 * NOTE: This can not work for file based sessions!
 *
 * @param int $userid user id
 */
function session_kill_user($userid) {
    global $CFG, $DB;

    // always check db table - custom session classes use sessions table
    try {
        $DB->delete_records('sessions', array('userid'=>$userid));
    } catch (dml_exception $ignored) {
        // do not show any warnings - might be during upgrade/installation
    }

    if (session_is_legacy()) {
        // log error?
    }
}

/**
 * Session garbage collection
 * - verify timeout for all users
 * - kill sessions of all deleted users
 * - kill sessions of users with disabled plugins or 'nologin' plugin
 *
 * NOTE: this can not work when legacy file sessions used!
 */
function session_gc() {
    global $CFG, $DB;

    $maxlifetime = $CFG->sessiontimeout;

    try {
        /// kill all sessions of deleted users
        $DB->delete_records_select('sessions', "userid IN (SELECT id FROM {user} WHERE deleted <> 0)");

        /// kill sessions of users with disabled plugins
        $auth_sequence = get_enabled_auth_plugins(true);
        $auth_sequence = array_flip($auth_sequence);
        unset($auth_sequence['nologin']); // no login allowed
        $auth_sequence = array_flip($auth_sequence);
        $notplugins = null;
        list($notplugins, $params) = $DB->get_in_or_equal($auth_sequence, SQL_PARAMS_QM, '', false);
        $DB->delete_records_select('sessions', "userid IN (SELECT id FROM {user} WHERE auth $notplugins)", $params);

        /// now get a list of time-out candidates
        $sql = "SELECT u.*, s.sid, s.timecreated AS s_timecreated, s.timemodified AS s_timemodified
                  FROM {user} u
                  JOIN {sessions} s ON s.userid = u.id
                 WHERE s.timemodified + ? < ? AND u.id <> ?";
        $params = array($maxlifetime, time(), $CFG->siteguest);

        $authplugins = array();
        foreach($auth_sequence as $authname) {
            $authplugins[$authname] = get_auth_plugin($authname);
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $user) {
            foreach ($authplugins as $authplugin) {
                if ($authplugin->ignore_timeout_hook($user, $user->sid, $user->s_timecreated, $user->s_timemodified)) {
                    continue;
                }
            }
            $DB->delete_records('sessions', array('sid'=>$user->sid));
        }
        $rs->close();

        // Extending the timeout period for guest sessions as they are renewed.
        $purgebefore = time() - $maxlifetime;
        $purgebeforeguests = time() - ($maxlifetime * 5);

        // delete expired sessions for guest user account
        $DB->delete_records_select('sessions', 'userid = ? AND timemodified < ?', array($CFG->siteguest, $purgebeforeguests));
        // delete expired sessions for userid = 0 (not logged in)
        $DB->delete_records_select('sessions', 'userid = 0 AND timemodified < ?', array($purgebefore));
    } catch (dml_exception $ex) {
        error_log('Error gc-ing sessions');
    }
}

/**
 * Makes sure that $USER->sesskey exists, if $USER itself exists. It sets a new sesskey
 * if one does not already exist, but does not overwrite existing sesskeys. Returns the
 * sesskey string if $USER exists, or boolean false if not.
 *
 * @uses $USER
 * @return string
 */
function sesskey() {
    // note: do not use $USER because it may not be initialised yet
    if (empty($_SESSION['USER']->sesskey)) {
        if (!isset($_SESSION['USER'])) {
            $_SESSION['USER'] = new stdClass;
        }
        $_SESSION['USER']->sesskey = random_string(10);
    }

    return $_SESSION['USER']->sesskey;
}


/**
 * Check the sesskey and return true of false for whether it is valid.
 * (You might like to imagine this function is called sesskey_is_valid().)
 *
 * Every script that lets the user perform a significant action (that is,
 * changes data in the database) should check the sesskey before doing the action.
 * Depending on your code flow, you may want to use the {@link require_sesskey()}
 * helper function.
 *
 * @param string $sesskey The sesskey value to check (optional). Normally leave this blank
 *      and this function will do required_param('sesskey', ...).
 * @return bool whether the sesskey sent in the request matches the one stored in the session.
 */
function confirm_sesskey($sesskey=NULL) {
    global $USER;

    if (!empty($USER->ignoresesskey)) {
        return true;
    }

    if (empty($sesskey)) {
        $sesskey = required_param('sesskey', PARAM_RAW);  // Check script parameters
    }

    return (sesskey() === $sesskey);
}

/**
 * Check the session key using {@link confirm_sesskey()},
 * and cause a fatal error if it does not match.
 */
function require_sesskey() {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
    }
}

/**
 * Sets a moodle cookie with a weakly encrypted username
 *
 * @param string $username to encrypt and place in a cookie, '' means delete current cookie
 * @return void
 */
function set_moodle_cookie($username) {
    global $CFG;

    if (NO_MOODLE_COOKIES) {
        return;
    }

    if (empty($CFG->rememberusername)) {
        // erase current and do not store permanent cookies
        $username = '';
    }

    if ($username === 'guest') {
        // keep previous cookie in case of guest account login
        return;
    }

    $cookiename = 'MOODLEID1_'.$CFG->sessioncookie;

    // delete old cookie
    setcookie($cookiename, '', time() - HOURSECS, $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $CFG->cookiesecure, $CFG->cookiehttponly);

    if ($username !== '') {
        // set username cookie for 60 days
        setcookie($cookiename, rc4encrypt($username), time()+(DAYSECS*60), $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $CFG->cookiesecure, $CFG->cookiehttponly);
    }
}

/**
 * Gets a moodle cookie with a weakly encrypted username
 *
 * @return string username
 */
function get_moodle_cookie() {
    global $CFG;

    if (NO_MOODLE_COOKIES) {
        return '';
    }

    if (empty($CFG->rememberusername)) {
        return '';
    }

    $cookiename = 'MOODLEID1_'.$CFG->sessioncookie;

    if (empty($_COOKIE[$cookiename])) {
        return '';
    } else {
        $username = rc4decrypt($_COOKIE[$cookiename]);
        if ($username === 'guest' or $username === 'nobody') {
            // backwards compatibility - we do not set these cookies any more
            $username = '';
        }
        return $username;
    }
}


/**
 * Setup $USER object - called during login, loginas, etc.
 *
 * Call sync_user_enrolments() manually after log-in, or log-in-as.
 *
 * @param stdClass $user full user record object
 * @return void
 */
function session_set_user($user) {
    $_SESSION['USER'] = $user;
    unset($_SESSION['USER']->description); // conserve memory
    if (isset($_SESSION['USER']->lang)) {
        // Make sure it is a valid lang pack name.
        $_SESSION['USER']->lang = clean_param($_SESSION['USER']->lang, PARAM_LANG);
    }
    sesskey(); // init session key

    if (PHPUNIT_TEST || defined('BEHAT_TEST')) {
        // phpunit tests use reversed reference
        global $USER;
        $USER = $_SESSION['USER'];
        $_SESSION['USER'] =& $USER;
    }
}

/**
 * Is current $USER logged-in-as somebody else?
 * @return bool
 */
function session_is_loggedinas() {
    return !empty($_SESSION['USER']->realuser);
}

/**
 * Returns the $USER object ignoring current login-as session
 * @return stdClass user object
 */
function session_get_realuser() {
    if (session_is_loggedinas()) {
        return $_SESSION['REALUSER'];
    } else {
        return $_SESSION['USER'];
    }
}

/**
 * Login as another user - no security checks here.
 * @param int $userid
 * @param stdClass $context
 * @return void
 */
function session_loginas($userid, $context) {
    global $USER;

    if (session_is_loggedinas()) {
        return;
    }

    // Switch to fresh new $SESSION.
    $_SESSION['REALSESSION'] = $_SESSION['SESSION'];
    $_SESSION['SESSION']     = new stdClass();

    /// Create the new $USER object with all details and reload needed capabilities
    $_SESSION['REALUSER'] = $_SESSION['USER'];
    $user = get_complete_user_data('id', $userid);
    $user->realuser       = $_SESSION['REALUSER']->id;
    $user->loginascontext = $context;

    // Let enrol plugins deal with new enrolments if necessary.
    enrol_check_plugins($user);

    // Create event before $USER is updated.
    $event = \core\event\user_loggedinas::create(
        array(
            'objectid' => $USER->id,
            'context' => $context,
            'relateduserid' => $userid,
            'other' => array(
                'originalusername' => fullname($USER, true),
                'loggedinasusername' => fullname($user, true)
                )
            )
        );
    // Set up global $USER.
    session_set_user($user);
    $event->trigger();
}

/**
 * Sets up current user and course environment (lang, etc.) in cron.
 * Do not use outside of cron script!
 *
 * @param stdClass $user full user object, null means default cron user (admin)
 * @param $course full course record, null means $SITE
 * @return void
 */
function cron_setup_user($user = NULL, $course = NULL) {
    global $CFG, $SITE, $PAGE;

    static $cronuser    = NULL;
    static $cronsession = NULL;

    if (empty($cronuser)) {
        /// ignore admins timezone, language and locale - use site default instead!
        $cronuser = get_admin();
        $cronuser->timezone = $CFG->timezone;
        $cronuser->lang     = '';
        $cronuser->theme    = '';
        unset($cronuser->description);

        $cronsession = new stdClass();
    }

    if (!$user) {
        // cached default cron user (==modified admin for now)
        session_set_user($cronuser);
        $_SESSION['SESSION'] = $cronsession;

    } else {
        // emulate real user session - needed for caps in cron
        if ($_SESSION['USER']->id != $user->id) {
            session_set_user($user);
            $_SESSION['SESSION'] = new stdClass();
        }
    }

    // TODO MDL-19774 relying on global $PAGE in cron is a bad idea.
    // Temporary hack so that cron does not give fatal errors.
    $PAGE = new moodle_page();
    if ($course) {
        $PAGE->set_course($course);
    } else {
        $PAGE->set_course($SITE);
    }

    // TODO: it should be possible to improve perf by caching some limited number of users here ;-)

}
