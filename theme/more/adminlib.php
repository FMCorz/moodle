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
 * Theme More admin lib.
 *
 * @package    theme_more
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom colour picker class.
 *
 * This is made to allow empty values for colours with a default. A decent
 * fallback should be set in the theme, or used from the parent.
 *
 * @package    theme_more
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_more_setting_configcolourpicker extends admin_setting_configcolourpicker {

    /**
     * Validates the colour that was entered by the user.
     *
     * @param string $data
     * @return string|false
     */
    protected function validate($data) {
        if (empty($data)) {
            // Prevent the default fallback on the default value.
            return '';
        }
        return parent::validate($data);
    }

}
