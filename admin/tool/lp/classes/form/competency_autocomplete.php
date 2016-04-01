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
 * Competency selector field.
 *
 * @package    tool_lp
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lp\form;

use MoodleQuickForm_autocomplete;

global $CFG;
require_once($CFG->libdir . '/form/autocomplete.php');


/**
 * Form field type for choosing a competency.
 *
 * @package    tool_lp
 * @copyright  2016 Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_autocomplete extends MoodleQuickForm_autocomplete {

    /** @var bool Only competencies not exceeding the maximum depth? */
    protected $potentialparentsonly = false;
    /** @var int The competency framework ID */
    protected $competencyframeworkid = null;

    /**
     * Constructor.
     *
     * @param string $elementname Element name
     * @param mixed $elementlabel Label(s) for an element
     * @param array $selected The values selected
     * @param array $default Other values selected by default and different from the competencies eg: framework.
     * @param array $options Options to control the element's display
     *                       Valid options are:
     *                       - multiple bool Whether or not the field accepts more than one values.
     *                       - potentialparentsonly bool Whether or not only competencies not exceeding the maximum depth.
     *                       - competencyframeworkid the competency framework id
     */
    public function __construct($elementname = null, $elementlabel = null, $selected = array(), $default =array(),
            $options = array()) {
        $this->potentialparentsonly = !empty($options['potentialparentsonly']);
        $this->competencyframeworkid = !empty($options['competencyframeworkid']) ? $options['competencyframeworkid'] : null;

        $validattributes = array(
            'ajax' => 'tool_lp/form-competency-selector',
            'data-potentialparentsonly' => $this->potentialparentsonly ? '1' : '0',
            'data-competencyframeworkid' => $this->competencyframeworkid,
        );
        if (!empty($options['multiple'])) {
            $validattributes['multiple'] = 'multiple';
        }
        if (!empty($options['potentialparentsonly'])) {
            $validattributes['data-frameworkmaxdepth'] = \tool_lp\competency_framework::get_taxonomies_max_level();
        }

        parent::__construct($elementname, $elementlabel, array(), $validattributes);

        // Select the competencies in the autocomplete element.
        if (!empty($selected)) {
            $competencies = $this->get_competencies($selected);
            $taxonomiesmaxlevel = \tool_lp\competency_framework::get_taxonomies_max_level();

            $selctedcompetenciesids = [];
            foreach ($competencies as $competency) {
                // If we have to check depth level when selecting a competency.
                if (!$this->potentialparentsonly || ($competency->get_level() < $taxonomiesmaxlevel)) {
                    $this->addOption($competency->get_shortname(), $competency->get_id());
                    array_push($selctedcompetenciesids, $competency->get_id());
                }
            }
            if (!empty($selctedcompetenciesids)) {
                $this->setSelected($selctedcompetenciesids);
            }
        } else if (!empty($default)) {
            // When we want a default value different from a competency eg: framework.
            foreach ($default as $key => $value) {
                $this->addOption($value, $key);
            }
            $this->setSelected(array_keys($default));
        }
    }

    /**
     * Set the value of this element.
     *
     * @param  string|array $value The value to set.
     * @return boolean
     */
    public function setValue($value) {
        global $DB;

        $values = (array) $value;
        $ids = [];

        foreach ($values as $onevalue) {
            if (($this->tags || $this->ajax) &&
                    (!$this->optionExists($onevalue)) &&
                    ($onevalue !== '_qf__force_multiselect_submission')) {
                array_push($ids, $onevalue);
            }
        }

        if (empty($ids)) {
            return;
        }

        // Logic here is simulating API.
        $taxonomiesmaxlevel = \tool_lp\competency_framework::get_taxonomies_max_level();
        $competencies = $this->get_competencies($ids);
        foreach ($competencies as $competency) {
            if (!$this->potentialparentsonly || ($competency->get_level() < $taxonomiesmaxlevel)) {
                if ( $this->optionExists($competency->get_id()) === false) {
                    $this->addOption($competency->get_shortname(), $competency->get_id());
                }
            }
        }

        return $this->setSelected(array_unique($ids));
    }

    /**
     * Get the list of competencies from competencies ids.
     *
     * @param array $selected Array of selected competencies ids
     * @return tool_lp\competency[] Array of competencies
     */
    protected function get_competencies($selected) {
        global $DB;
        $competencies = [];
        if (!empty($selected)) {
            list($insql, $inparams) = $DB->get_in_or_equal($selected, SQL_PARAMS_NAMED, 'param');
            $competencies = \tool_lp\competency::get_records_select("id $insql", $inparams, 'shortname');
        }
        return $competencies;
    }
}
