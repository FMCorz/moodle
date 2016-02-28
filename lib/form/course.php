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
 * Course selector field.
 *
 * Allows auto-complete ajax searching for courses and can restrict by enrolment, permissions, viewhidden...
 *
 * @package   core_form
 * @copyright 2015 Damyon Wiese <damyon@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->libdir . '/form/autocomplete.php');

/**
 * Form field type for choosing a course.
 *
 * Allows auto-complete ajax searching for courses and can restrict by enrolment, permissions, viewhidden...
 *
 * @package   core_form
 * @copyright 2015 Damyon Wiese <damyon@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_course extends MoodleQuickForm_autocomplete {

    /**
     * @var array $exclude Exclude a list of courses from the list (e.g. the current course).
     */
    protected $exclude = array();

    /**
     * @var boolean $allowmultiple Allow selecting more than one course.
     */
    protected $multiple = false;

    /**
     * @var array $requiredcapabilities Array of extra capabilities to check at the course context.
     */
    protected $requiredcapabilities = array();

    /**
     * Constructor
     *
     * @param string $elementName Element name
     * @param mixed $elementLabel Label(s) for an element
     * @param array $options Options to control the element's display
     */
    function __construct($elementName = null, $elementLabel = null, $options = array()) {
        if (isset($options['multiple'])) {
            $this->multiple = $options['multiple'];
        }
        if (isset($options['exclude'])) {
            $this->exclude = $options['exclude'];
        }
        if (isset($options['requiredcapabilities'])) {
            $this->requiredcapabilities = $options['requiredcapabilities'];
        }

        $validattributes = array(
            'ajax' => 'core/form-course-selector',
            'data-requiredcapabilities' => implode(',', $this->requiredcapabilities),
            'data-exclude' => implode(',', $this->exclude)
        );
        if ($this->multiple) {
            $validattributes['multiple'] = 'multiple';
        }

        parent::__construct($elementName, $elementLabel, array(), $validattributes);
    }

    /**
     * Set the value of this element. If values can be added or are unknown, we will
     * make sure they exist in the options array.
     * @param  mixed string|array $value The value to set.
     * @return boolean
     */
    function setValue($value) {
        $values = (array) $value;

        foreach ($values as $onevalue) {
            if (($this->tags || $this->ajax) &&
                    (!$this->optionExists($onevalue)) &&
                    ($onevalue !== '_qf__force_multiselect_submission')) {
                // We need custom behaviour to fetch the course info.
                $course = get_course($onevalue);
                $context = context_course::instance($course->id);
                $this->addOption(format_string($course->fullname, true, array('context' => $context)), $onevalue);
            }
        }
        return $this->setSelected($value);
    }
}
