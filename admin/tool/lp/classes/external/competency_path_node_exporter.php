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
 * Class for exporting competency_path_node data.
 *
 * @package    tool_lp
 * @copyright  2016 Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lp\external;
defined('MOODLE_INTERNAL') || die();

use renderer_base;
use tool_lp\competency;

/**
 * Class for exporting competency_path_node data.
 *
 * @copyright  2016 Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_path_node_exporter extends exporter {

    /**
     * Constructor of competency_path_node.
     *
     * @param competency $comp - the competency.
     * @param array $related - related objects.
     */
    public function __construct(competency $comp, $related) {
        $data = [
            'id' => $comp->get_id(),
            'shortname' => $comp->get_shortname(),
        ];
        parent::__construct($data, $related);
    }

    /**
     * Returns a list of objects that are related to this persistent.
     *
     * @return array
     */
    protected static function define_related() {
        return ['pathinfo' => 'stdClass', 'context' => 'context'];
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT
            ],
            'shortname' => [
                'type' => PARAM_TEXT
            ]
        ];
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array - Keys with their types.
     */
    protected static function define_other_properties() {
        return [
            'first' => [
                'type' => PARAM_BOOL
            ],
            'last' => [
                'type' => PARAM_BOOL
            ],
            'position' => [
                'type' => PARAM_INT
            ]
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $position = $this->related['pathinfo']->position;
        $max = $this->related['pathinfo']->max;
        return [
            'first' => ($position == 1) ? true : false,
            'last' => ($position == $max) ? true : false,
            'position' => $position
        ];
    }
}
