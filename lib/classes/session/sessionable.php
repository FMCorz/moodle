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
 * Interface for any session class.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;
defined('MOODLE_INTERNAL') || die();

/**
 * Interface for any session class.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface sessionable {

    /**
     * Terminate current session.
     *
     * @return void
     */
    public function terminate_current();

    /**
     * No more changes in session expected.
     * Unblocks the sessions, other scripts may start executing in parallel.
     *
     * @return void
     */
    public function write_close();

    /**
     * Check for existing session with id $sid.
     *
     * @param mixed $sid
     * @return boolean true if session found.
     */
    public function session_exists($sid);

}
