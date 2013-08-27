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
 * Legacy file session driver.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;
defined('MOODLE_INTERNAL') || die();

/**
 * Legacy moodle sessions stored in files, not recommended any more.
 *
 * @package    core
 * @subpackage session
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class driver_legacyfile extends \core\session\driver {
    /**
     * Init session storage.
     */
    protected function init_session_storage() {
        global $CFG;

        ini_set('session.save_handler', 'files');

        // Some distros disable GC by setting probability to 0
        // overriding the PHP default of 1
        // (gc_probability is divided by gc_divisor, which defaults to 1000)
        if (ini_get('session.gc_probability') == 0) {
            ini_set('session.gc_probability', 1);
        }

        ini_set('session.gc_maxlifetime', $CFG->sessiontimeout);

        // make sure sessions dir exists and is writable, throws exception if not
        make_upload_directory('sessions');

        // Need to disable debugging since disk_free_space()
        // will fail on very large partitions (see MDL-19222)
        $freespace = @disk_free_space($CFG->dataroot.'/sessions');
        if (!($freespace > 2048) and $freespace !== false) {
            print_error('sessiondiskfull', 'error');
        }
        ini_set('session.save_path', $CFG->dataroot .'/sessions');
    }
    /**
     * Check for existing session with id $sid
     * @param unknown_type $sid
     * @return boolean true if session found.
     */
    public function session_exists($sid){
        global $CFG;

        $sid = clean_param($sid, PARAM_FILE);
        $sessionfile = "$CFG->dataroot/sessions/sess_$sid";
        return file_exists($sessionfile);
    }
}
