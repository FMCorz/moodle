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
 * User selector class.
 *
 * @package    tool_lp
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_user_selector extends \user_selector_base {

    public function __construct($name, $options = array()) {
        $this->multiselect = true;
        $this->rows = 10;
        parent::__construct($name, $options);
    }

    public function find_users($search) {
        global $DB;

        $fields = $this->required_fields_sql('u');

        list($wheresql, $whereparams) = $this->search_sql($search, 'u');
        list($sortsql, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);

        $countsql = "SELECT COUNT('x') FROM {user} u WHERE $wheresql";
        $countparams = $whereparams;
        $sql = "SELECT $fields FROM {user} u WHERE $wheresql ORDER BY $sortsql";
        $params = $whereparams + $sortparams;

        if (!$this->is_validating()) {
            $count = $DB->count_records_sql($countsql, $countparams);
            if ($count > $this->maxusersperpage) {
                return $this->too_many_results($search, $count);
            }
        }

        $group = 'matching';
        return array(
            $group => $DB->get_records_sql($sql, $params)
        );
    }

}
