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
 * User selector file.
 *
 * @package    tool_lp
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lp\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');

/**
 * User selected class.
 *
 * @package    tool_lp
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_user_selected extends \user_selector_base {

    public function __construct($name, $options = array()) {
        $this->multiselect = true;
        $this->rows = 10;
        parent::__construct($name, $options);
    }

    public function add_users(array $users) {
        globaL $SESSION;
        if (!isset($SESSION->template_user_selected)) {
            $SESSION->template_user_selected = array();
        }

        foreach ($users as $user) {
            $SESSION->template_user_selected[$user->id] = $user;
        }
    }

    public function get_users() {
        global $SESSION;
        if (!isset($SESSION->template_user_selected)) {
            $SESSION->template_user_selected = array();
        }
        return $SESSION->template_user_selected;
    }

    public function has_users() {
        global $SESSION;
        return !empty($SESSION->template_user_selected);
    }

    public function find_users($search) {
        global $SESSION;

        if (empty($search)) {
            return array('matching' => $this->get_users());
        }

        // Very basic search.
        $preg = '/' . preg_quote($search) . '/i';
        error_log($preg);
        $fields = explode(',', $this->required_fields_sql(''));
        $users = array();
        foreach ($this->get_users() as $user) {
            foreach ($fields as $field) {
                if (isset($user->$field) && preg_match($preg, $user->$field)) {
                    $users[] = $user;
                    break;
                }
            }
        }

        $group = 'matching';
        return array(
            $group => $users
        );
    }

    public function purge_users() {
        global $SESSION;
        $SESSION->template_user_selected = array();
    }

    public function remove_users(array $users) {
        global $SESSION;
        if (!isset($SESSION->template_user_selected)) {
            $SESSION->template_user_selected = array();
        }

        foreach ($users as $user) {
            unset($SESSION->template_user_selected[$user->id]);
        }
    }

}
