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
 * Emergency session.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;
defined('MOODLE_INTERNAL') || die();

/**
 * Fallback session handler when standard session init fails.
 * This prevents repeated attempts to init faulty handler.
 *
 * @package    core
 * @subpackage session
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class emergency implements sessionable {

    public function __construct() {
        // session not used at all
        $_SESSION = array();
        $_SESSION['SESSION'] = new \stdClass();
        $_SESSION['USER']    = new \stdClass();
    }

    /**
     * Terminate current session
     * @return void
     */
    public function terminate_current() {
        return;
    }

    /**
     * No more changes in session expected.
     * Unblocks the sessions, other scripts may start executing in parallel.
     * @return void
     */
    public function write_close() {
        return;
    }

    /**
     * Check for existing session with id $sid
     * @param unknown_type $sid
     * @return boolean true if session found.
     */
    public function session_exists($sid) {
        return false;
    }
}
