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
 * Memcached session handler.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\session;
defined('MOODLE_INTERNAL') || die();

/**
 * Memcached session handler.
 *
 * @package    core
 * @subpackage session
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Mark Nielsen
 */
class driver_memcached extends driver {

    /**
     * Initialise the storage.
     *
     * @return void
     */
    protected function init_session_storage() {
        global $CFG;

        $memcachedversion = phpversion('memcached');
        if (!$memcachedversion || version_compare($memcachedversion, '2.0') < 0) {
            throw new \moodle_exception('Memcached session driver requires Memcached extension version >= 2.0.');
        } else if (!defined('SESSION_DRIVER_MEMCACHED_SAVE_PATH')) {
            throw new \coding_exception('Constant SESSION_DRIVER_MEMCACHED_SAVE_PATH must be set for Memcached sessions.');
        }
        ini_set('session.save_handler', 'memcached');
        ini_set('session.save_path', SESSION_DRIVER_MEMCACHED_SAVE_PATH);
        ini_set('session.gc_maxlifetime', $CFG->sessiontimeout);
    }

    /**
     * Checks if the session exists.
     *
     * @param string $sid session ID.
     * @return boolean true when it exists.
     */
    public function session_exists($sid) {

        $memcached = new \Memcached();
        $memcached->addServers($this->connection_string_to_servers());
        $value = $memcached->get(ini_get('memcached.sess_prefix') . $sid);
        $memcached->quit();

        if ($value !== false) {
            return true;
        }
        return false;
    }

    /**
     * Convert a connection string to an array of servers.
     *
     * EG: Converts: "abc:123, xyz:789" to
     *
     *  array(
     *      array('abc', '123'),
     *      array('xyz', '789'),
     *  )
     *
     * @see self::session_exists()
     * @uses SESSION_DRIVER_MEMCACHED_SAVE_PATH
     * @return array array(0 => array(host, port), 1 => ...)
     */
    protected function connection_string_to_servers() {
        $servers = array();
        $parts   = explode(',', SESSION_DRIVER_MEMCACHED_SAVE_PATH);
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
