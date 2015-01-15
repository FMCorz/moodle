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
 * Message ajax.
 *
 * @package    core_message
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('AJAX_SCRIPT', true);

require('../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/externallib.php');

// Only real logged in users.
require_login(null, false);
if (isguestuser()) {
    core_message_invalid_request();
}

// Messaging needs to be enabled.
if (empty($CFG->messaging)) {
    core_message_invalid_request();
}

require_sesskey();
$action = optional_param('action', null, PARAM_ALPHA);
$response = null;

switch ($action) {
    case 'getmessages':

        $userid = required_param('userid', PARAM_INT);
        if (empty($userid) || isguestuser($userid) || $userid == $USER->id) {
            core_message_invalid_request();
        }

        $user1 = (object) array('id' => $USER->id);
        $user2 = (object) array('id' => $userid);

        $messages = message_get_history($user1, $user2, 25);
        foreach ($messages as $key => $message) {
            $messages[$key]->text = message_format_message_text($message, true);
            $messages[$key]->time = userdate($message->timecreated);
        }

        // Reset the keys.
        $response = array_values($messages);
        break;

    case 'sendmessage':

        require_capability('moodle/site:sendmessage', context_system::instance());

        $userid = required_param('userid', PARAM_INT);
        if (empty($userid) || isguestuser($userid) || $userid == $USER->id) {
            // Cannot send messags to self, nobody or a guest.
            core_message_invalid_request();
        }

        $message = required_param('message', PARAM_RAW);
        $user2 = core_user::get_user($userid);
        $messageid = message_post_message($USER, $user2, $message, FORMAT_MOODLE);

        if ($messageid) {
            $message = $DB->get_record('message', array('id' => $messageid), '*', MUST_EXIST);
            $message->text = message_format_message_text($message, true);
            $message->time = userdate($message->timecreated);
            $response = $message;
        }

        break;
}

if ($response) {
    echo json_encode($response);
    exit();
}

core_message_invalid_request();

/**
 * Handles an invalid request.
 * @throws moodle_exception
 * @return void
 */
function core_message_invalid_request() {
    throw new moodle_exception('Invalid request');
}
