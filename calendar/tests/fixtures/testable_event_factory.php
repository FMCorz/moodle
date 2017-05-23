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
 * CM Event factory class.
 *
 * @package    core_calendar
 * @copyright  2017 Frédéric Massart <fred@fmcorz.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\action_event;
use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\factories\event_factory;
use core_calendar\local\event\factories\event_abstract_factory;
use core_calendar\local\event\mappers\event_mapper_interface;
use core_calendar\local\event\mappers\event_mapper;

/**
 * CM Event factory class.
 *
 * @package    core_calendar
 * @copyright  2017 Frédéric Massart <fred@fmcorz.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_testable_event_factory extends event_abstract_factory {

    protected function apply_component_action(event_interface $event) {
        return new action_event(
            $event,
            new \core_calendar\local\event\value_objects\action(
                'test',
                new \moodle_url('http://example.com'),
                420,
                true
            ));
    }

    protected function expose_event(event_interface $event) {
        return $event;
    }

    protected function should_bail(\stdClass $dbrow) {
        // At present we only have a bail-out check for events in course modules.
        if (empty($dbrow->modulename)) {
            return false;
        }

        $instances = get_fast_modinfo($dbrow->courseid)->instances;

        // If modinfo doesn't know about the module, we should ignore it.
        if (!isset($instances[$dbrow->modulename]) || !isset($instances[$dbrow->modulename][$dbrow->instance])) {
            return true;
        }

        $cm = $instances[$dbrow->modulename][$dbrow->instance];

        // If the module is not visible to the current user, we should ignore it.
        // We have to check enrolment here as well because the uservisible check
        // looks for the "view" capability however some activities (such as Lesson)
        // have that capability set on the "Authenticated User" role rather than
        // on "Student" role, which means uservisible returns true even when the user
        // is no longer enrolled in the course.
        $modulecontext = \context_module::instance($cm->id);
        // A user with the 'moodle/course:view' capability is able to see courses
        // that they are not a participant in.
        $canseecourse = (has_capability('moodle/course:view', $modulecontext) || is_enrolled($modulecontext));
        if (!$cm->uservisible || !$canseecourse) {
            return true;
        }

        // Ok, now check if we are looking at a completion event.
        if ($dbrow->eventtype === \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED) {
            // Need to have completion enabled before displaying these events.
            $course = new \stdClass();
            $course->id = $dbrow->courseid;
            $completion = new \completion_info($course);

            return (bool) !$completion->is_enabled($cm);
        }

        return false;
    }
}
