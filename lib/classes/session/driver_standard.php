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
 * File for standard session driver.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;
defined('MOODLE_INTERNAL') || die();

/**
 * Recommended moodle session driver.
 *
 * @package    core
 * @subpackage session
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class driver_standard extends driver {

    /** @var stdClass $record session record */
    protected $record   = null;

    /** @var moodle_database $database session database */
    protected $database = null;

    /** @var bool $failed session read/init failed, do not write back to DB */
    protected $failed   = false;

    /** @var string hash of the session data content */
    protected $lasthash = null;

    /** @const MySQL error state value. */
    const STATE_ERROR_MYSQL = 9;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $this->database = $DB;
        parent::__construct();

        if (!empty($this->record->state)) {
            // Something is very wrong.
            session_kill($this->record->sid);

            if ($this->record->state == self::STATE_ERROR_MYSQL) {
                print_error('dbsessionmysqlpacketsize', 'error');
            }
        }
    }

    /**
     * Check for existing session with id $sid.
     * @param string $sid
     * @return boolean true if session found.
     */
    public function session_exists($sid){
        global $CFG;
        try {
            $sql = "SELECT * FROM {sessions} WHERE timemodified < ? AND sid=? AND state=?";
            $params = array(time() + $CFG->sessiontimeout, $sid, 0);
            return $this->database->record_exists_sql($sql, $params);
        } catch (\dml_exception $ex) {
            error_log('Error checking existance of database session');
            return false;
        }
    }

    /**
     * Init session storage.
     * @return void
     */
    protected function init_session_storage() {
        global $CFG;

        // GC only from CRON - individual user timeouts now checked during each access.
        ini_set('session.gc_probability', 0);
        ini_set('session.gc_maxlifetime', $CFG->sessiontimeout);

        $result = session_set_save_handler(
            array($this, 'handler_open'),
            array($this, 'handler_close'),
            array($this, 'handler_read'),
            array($this, 'handler_write'),
            array($this, 'handler_destroy'),
            array($this, 'handler_gc')
        );
        if (!$result) {
            print_error('dbsessionhandlerproblem', 'error');
        }
    }

    /**
     * Open session handler.
     *
     * {@see http://php.net/manual/en/function.session-set-save-handler.php}
     *
     * @param string $save_path
     * @param string $session_name
     * @return bool success
     */
    public function handler_open($save_path, $session_name) {
        return true;
    }

    /**
     * Close session handler.
     *
     * {@see http://php.net/manual/en/function.session-set-save-handler.php}
     *
     * @return bool success
     */
    public function handler_close() {
        if (isset($this->record->id)) {
            try {
                $this->database->release_session_lock($this->record->id);
            } catch (\Exception $ex) {
                // Ignore any problems.
            }
        }
        $this->record = null;
        return true;
    }

    /**
     * Read session handler.
     *
     * {@see http://php.net/manual/en/function.session-set-save-handler.php}
     *
     * @param string $sid
     * @return string
     */
    public function handler_read($sid) {
        global $CFG;

        if ($this->record and $this->record->sid != $sid) {
            error_log('Weird error reading database session - mismatched sid');
            $this->failed = true;
            return '';
        }

        try {
            // Do not fetch full record yet, wait until it is locked.
            if (!$record = $this->database->get_record('sessions', array('sid'=>$sid), 'id, userid')) {
                $record = new \stdClass();
                $record->state        = 0;
                $record->sid          = $sid;
                $record->sessdata     = null;
                $record->userid       = 0;
                $record->timecreated  = $record->timemodified = time();
                $record->firstip      = $record->lastip = getremoteaddr();
                $record->id           = $this->database->insert_record_raw('sessions', $record);
            }
        } catch (\Exception $ex) {
            // Do not rethrow exceptions here, we need this to work somehow before 1.9.x upgrade and during install.
            error_log('Can not read or insert database sessions');
            $this->failed = true;
            return '';
        }

        try {
            if (!empty($CFG->sessionlockloggedinonly) and (isguestuser($record->userid) or empty($record->userid))) {
                // No session locking for guests and not-logged-in users,
                // these users mostly read stuff, there should not be any major
                // session race conditions. Hopefully they do not access other
                // pages while being logged-in.
            } else {
                $this->database->get_session_lock($record->id, SESSION_ACQUIRE_LOCK_TIMEOUT);
            }
        } catch (\Exception $ex) {
            // This is a fatal error, better inform users.
            // It should not happen very often - all pages that need long time to execute
            // should close session soon after access control checks
            error_log('Can not obtain session lock');
            $this->failed = true;
            throw $ex;
        }

        // Finally read the full session data because we know we have the lock now.
        if (!$record = $this->database->get_record('sessions', array('id'=>$record->id))) {
            error_log('Cannot read session record');
            $this->failed = true;
            return '';
        }

        // Verify timeout.
        if ($record->timemodified + $CFG->sessiontimeout < time()) {
            $ignoretimeout = false;
            if (!empty($record->userid)) { // Skips not logged in.
                if ($user = $this->database->get_record('user', array('id'=>$record->userid))) {

                    // Refresh session if logged as a guest.
                    if (isguestuser($user)) {
                        $ignoretimeout = true;
                    } else {
                        $authsequence = get_enabled_auth_plugins(); // Auths, in sequence.
                        foreach($authsequence as $authname) {
                            $authplugin = get_auth_plugin($authname);
                            if ($authplugin->ignore_timeout_hook($user, $record->sid, $record->timecreated, $record->timemodified)) {
                                $ignoretimeout = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ($ignoretimeout) {
                // Refresh session.
                $record->timemodified = time();
                try {
                    $this->database->update_record('sessions', $record);
                } catch (\Exception $ex) {
                    // Very unlikely error.
                    error_log('Can not refresh database session');
                    $this->failed = true;
                    throw $ex;
                }
            } else {
                // Time out session.
                $record->state        = 0;
                $record->sessdata     = null;
                $record->userid       = 0;
                $record->timecreated  = $record->timemodified = time();
                $record->firstip      = $record->lastip = getremoteaddr();
                try {
                    $this->database->update_record('sessions', $record);
                } catch (\Exception $ex) {
                    // Very unlikely error.
                    error_log('Can not time out database session');
                    $this->failed = true;
                    throw $ex;
                }
            }
        }

        if (is_null($record->sessdata)) {
            $data = '';
            $this->lasthash = sha1('');
        } else {
            $data = base64_decode($record->sessdata);
            $this->lasthash = sha1($record->sessdata);
        }

        unset($record->sessdata); // Conserve memory.
        $this->record = $record;

        return $data;
    }

    /**
     * Write session handler.
     *
     * {@see http://php.net/manual/en/function.session-set-save-handler.php}
     *
     * NOTE: Do not write to output or throw any exceptions!
     *       Hopefully the next page is going to display nice error or it recovers...
     *
     * @param string $sid
     * @param string $session_data
     * @return bool success
     */
    public function handler_write($sid, $session_data) {
        global $USER;

        // TODO: MDL-20625 we need to rollback all active transactions and log error if any open needed

        if ($this->failed) {
            // Do not write anything back - we failed to start the session properly.
            return false;
        }

        $userid = 0;
        if (!empty($USER->realuser)) {
            $userid = $USER->realuser;
        } else if (!empty($USER->id)) {
            $userid = $USER->id;
        }

        if (isset($this->record->id)) {
            $data = base64_encode($session_data);  // There might be some binary mess :-(!

            // Skip db update if nothing changed, do not update the timemodified each second.
            $hash = sha1($data);
            if ($this->lasthash === $hash
                    and $this->record->userid == $userid
                    and (time() - $this->record->timemodified < 20)
                    and $this->record->lastip == getremoteaddr()) {
                // No need to update anything!
                return true;
            }

            $this->record->sessdata     = $data;
            $this->record->userid       = $userid;
            $this->record->timemodified = time();
            $this->record->lastip       = getremoteaddr();

            try {
                $this->database->update_record_raw('sessions', $this->record);
                $this->lasthash = $hash;
            } catch (\dml_exception $ex) {
                if ($this->database->get_dbfamily() === 'mysql') {
                    try {
                        $this->database->set_field('sessions', 'state', self::STATE_ERROR_MYSQL, array('id'=>$this->record->id));
                    } catch (\Exception $ignored) {
                    }
                    error_log('Can not write database session - please verify max_allowed_packet is at least 4M!');
                } else {
                    error_log('Can not write database session');
                }
                return false;
            } catch (\Exception $ex) {
                error_log('Can not write database session');
                return false;
            }

        } else {
            // Fresh new session.
            try {
                $record = new \stdClass();
                $record->state        = 0;
                $record->sid          = $sid;
                $record->sessdata     = base64_encode($session_data); // There might be some binary mess :-(!
                $record->userid       = $userid;
                $record->timecreated  = $record->timemodified = time();
                $record->firstip      = $record->lastip = getremoteaddr();
                $record->id           = $this->database->insert_record_raw('sessions', $record);

                $this->record = $this->database->get_record('sessions', array('id'=>$record->id));
                $this->lasthash = sha1($record->sessdata);

                $this->database->get_session_lock($this->record->id, SESSION_ACQUIRE_LOCK_TIMEOUT);
            } catch (\Exception $ex) {
                // This should not happen.
                error_log('Can not write new database session or acquire session lock');
                $this->failed = true;
                return false;
            }
        }

        return true;
    }

    /**
     * Destroy session handler.
     *
     * {@see http://php.net/manual/en/function.session-set-save-handler.php}
     *
     * @param string $sid
     * @return bool success
     */
    public function handler_destroy($sid) {
        session_kill($sid);

        if (isset($this->record->id) and $this->record->sid === $sid) {
            try {
                $this->database->release_session_lock($this->record->id);
            } catch (\Exception $ex) {
                // Ignore problems.
            }
            $this->record = null;
        }

        $this->lasthash = null;

        return true;
    }

    /**
     * GC session handler.
     *
     * {@see http://php.net/manual/en/function.session-set-save-handler.php}
     *
     * @param int $ignored_maxlifetime moodle uses special timeout rules
     * @return bool success
     */
    public function handler_gc($ignored_maxlifetime) {
        session_gc();
        return true;
    }
}
