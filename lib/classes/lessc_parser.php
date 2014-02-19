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
 * Core LESS parser.
 *
 * @package    core
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/lessphp/lessc.inc.php');

/**
 * Core LESS parser class.
 *
 * @package    core
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_lessc_parser extends lessc_parser {

    /**
     * Override of the function to extend support for our custom CSS.
     *
     * @return bool
     */
    protected function parseChunk() {
        if (empty($this->buffer)) {
            return false;
        }

        // Store pointer position.
        $s = $this->seek();

        $custom = '';
        // Custom Moodle rule [[xxxx:yyyy]]. This is intended to only work when on its own line.
        // For example with [[setting:customcss]].
        if ($this->literal('[[') && $this->match('([^\n\]]+)\]\](\n|\Z)', $custom)) {
            $this->append(array('moodle_custom', '[[' . $custom[1] . ']]', null), $s);
            return true;
        }

        // Restore curstor position and let the parent proceed with this.
        $this->seek($s);
        return parent::parseChunk();
    }

}
