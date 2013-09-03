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
 * Add event handlers for the quiz
 *
 * @package    mod_quiz
 * @category   event
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


$handlers = array(
    // Handle group events, so that open quiz attempts with group overrides get
    // updated check times.
    'groups_member_added' => array (
        'handlerfile'     => '/mod/quiz/locallib.php',
        'handlerfunction' => 'quiz_groups_member_added_handler',
        'schedule'        => 'instant',
    ),
    'groups_member_removed' => array (
        'handlerfile'     => '/mod/quiz/locallib.php',
        'handlerfunction' => 'quiz_groups_member_removed_handler',
        'schedule'        => 'instant',
    ),
    'groups_members_removed' => array (
        'handlerfile'     => '/mod/quiz/locallib.php',
        'handlerfunction' => 'quiz_groups_members_removed_handler',
        'schedule'        => 'instant',
    ),
    'groups_group_deleted' => array (
        'handlerfile'     => '/mod/quiz/locallib.php',
        'handlerfunction' => 'quiz_groups_group_deleted_handler',
        'schedule'        => 'instant',
    ),
);

$observers = array(
    // Handle our own \mod_quiz\event\attempt_timelimit_exceeded event, to email
    // the student to let them know they forgot to submit, and that they have another chance.
    array(
        'eventname' => '\mod_quiz\event\attempt_timelimit_exceeded',
        'includefile' => '/mod/quiz/locallib.php',
        'callback' => 'quiz_attempt_overdue_handler',
        'internal' => false,
    ),

    // Handle our own \mod_quiz\event\attempt_submitted event, as a way to
    // send confirmation messages asynchronously.
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'includefile'     => '/mod/quiz/locallib.php',
        'callback' => 'quiz_attempt_submitted_handler',
        'internal' => false
    ),

);
