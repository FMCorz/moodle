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
 * Unit tests for group lib.
 *
 * @package    core_group
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Group lib testcase.
 *
 * @package    core_group
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_group_lib_testcase extends advanced_testcase {

    public function test_member_added_event() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $sink = $this->redirectEvents();
        groups_add_member($group->id, $user->id, 'mod_workshop', '123');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        $expected = new stdClass();
        $expected->groupid = $group->id;
        $expected->userid  = $user->id;
        $expected->component = 'mod_workshop';
        $expected->itemid = '123';
        $this->assertEventLegacyData($expected, $event);
        $this->assertInstanceOf('\core\event\group_member_added', $event);
        $this->assertEquals($user->id, $event->relateduserid);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals($group->id, $event->objectid);
    }
}
