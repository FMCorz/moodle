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

require_login();
require_sesskey();

$action = optional_param('action', null, PARAM_ALPHA);
$response = null;

switch ($action) {
    case 'getmessages':

        $userid = required_param('userid', PARAM_INT);
        if (empty($userid) || isguestuser($userid) || $userid == $USER->id) {
            throw new moodle_exception('Invalid request');
        }

        $user1 = (object) array('id' => $USER->id);
        $user2 = (object) array('id' => $userid);

        $messages = message_get_history($user1, $user2, 25);
        foreach ($messages as $key => $message) {
            $messages[$key]->text = message_format_message_text($message, true);
        }

        $response = $messages;
        break;
}

if ($response) {
    echo json_encode($response);
    exit();
}

send_header_404();
