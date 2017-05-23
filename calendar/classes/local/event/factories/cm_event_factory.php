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
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\factories;

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\action_event;
use core_calendar\local\event\entities\action_event_interface;
use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\factories\event_factory;
use core_calendar\local\event\mappers\event_mapper_interface;
use core_calendar\local\event\mappers\event_mapper;

/**
 * CM Event factory class.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm_event_factory extends event_abstract_factory {

    protected $eventmapper;
    protected $actionfactory;

    public function __construct(
        event_mapper_interface $eventmapper,
        action_factory $actionfactory,
        array &$coursecachereference,
        array &$modulecachereference
    ) {
        parent::__construct($coursecachereference, $modulecachereference);
        $this->eventmapper = $eventmapper;
        $this->actionfactory = $actionfactory;
    }

    protected function apply_component_action(event_interface $event) {
        // Callbacks will get supplied a "legacy" version
        // of the event class.
        $mapper = $this->eventmapper;
        $action = null;
        if ($event->get_course_module()) {
            // TODO MDL-58866 Only activity modules currently support this callback.
            // Any other event will not be displayed on the dashboard.
            $action = component_callback(
                'mod_' . $event->get_course_module()->get('modname'),
                'core_calendar_provide_event_action',
                [
                    $mapper->from_event_to_legacy_event($event),
                    $this->actionfactory
                ]
            );
        }

        // If we get an action back, return an action event, otherwise
        // continue piping through the original event.
        //
        // If a module does not implement the callback, component_callback
        // returns null.
        return $action ? new action_event($event, $action) : $event;
    }

    protected function expose_event(event_interface $event) {
        $mapper = $this->$eventmapper;
        $eventvisible = null;
        if ($event->get_course_module()) {
            // TODO MDL-58866 Only activity modules currently support this callback.
            $eventvisible = component_callback(
                'mod_' . $event->get_course_module()->get('modname'),
                'core_calendar_is_event_visible',
                [
                    $mapper->from_event_to_legacy_event($event)
                ]
            );
        }

        // Do not display the event if there is nothing to action.
        if ($event instanceof action_event_interface && $event->get_action()->get_item_count() === 0) {
            return null;
        }

        // Module does not implement the callback, event should be visible.
        if (is_null($eventvisible)) {
            return $event;
        }

        return $eventvisible ? $event : null;
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
