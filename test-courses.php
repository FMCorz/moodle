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
 * TESTING PAGE for autocomplete field.
 */

require('config.php');
require_once("$CFG->libdir/formslib.php");

class test_form extends moodleform {

    function definition() {
        $mform  = $this->_form;

        $mform->addElement('header','general', 'Test autocomplete field');

        $options = array('showhidden' => true, 'requiredcapabilities' => array('moodle/badges:awardbadge'));
        $mform->addElement('course', 'link', 'Single', $options);
        $mform->addRule('link', get_string('required'), 'required', null, 'client');

        $options = array('showhidden' => true, 'requiredcapabilities' => array('moodle/badges:awardbadge'), 'multiple' => true);
        $mform->addElement('course', 'link2', 'Multi', $options);
        $mform->addRule('link2', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        var_dump($data);
        var_dump($errors);

        $errors['link'] = 'Some error - does it display?';

        return $errors;
    }
}


$PAGE->set_url('/test-courses.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());
require_login();

$mform = new test_form();

if ($mform->is_cancelled()) {
    die('Cancelled');

} else if ($data = $mform->get_data()) {
    var_dump($data);
    die('Submitted');
}

$PAGE->set_heading('Test');
$PAGE->set_title('Auto complete');

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
