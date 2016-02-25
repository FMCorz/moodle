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
 * Form.
 *
 * @package    tool_cbemigrate
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cbemigrate\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class.
 *
 * @package    tool_cbemigrate
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_framework extends \moodleform {

    protected $pagecontext;

    public function __construct(\context $context) {
        $this->pagecontext = $context;
        parent::__construct();
    }

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'hdrcourses', get_string('frameworks', 'tool_cbemigrate'));

        $mform->addElement('autocomplete', 'from', get_string('migratefrom', 'tool_cbemigrate'), null, array(
            'ajax' => 'tool_cbemigrate/frameworks_datasource',
            'data-contextid' => $this->pagecontext->id,
            'data-onlyvisible' => '0',
        ));
        $mform->addRule('from', get_string('required'), 'required', null);
        $mform->addHelpButton('from', 'migratefrom', 'tool_cbemigrate');

        $mform->addElement('autocomplete', 'to', 'Migrate to', null, array(
            'ajax' => 'tool_cbemigrate/frameworks_datasource',
            'data-contextid' => $this->pagecontext->id,
            'data-onlyvisible' => '1',      // We cannot add competencies from hidden frameworks, so it must be visible.
        ));
        $mform->addRule('to', get_string('required'), 'required', null);
        $mform->addHelpButton('to', 'migrateto', 'tool_cbemigrate');

        $mform->addElement('header', 'hdrcourses', 'Courses');
        $mform->addElement('course', 'allowedcourses', get_string('limittothese', 'tool_cbemigrate'), array('showhidden' => true, 'multiple' => true));
        $mform->addHelpButton('allowedcourses', 'allowedcourses', 'tool_cbemigrate');
        $mform->addElement('course', 'disallowedcourses', get_string('excludethese', 'tool_cbemigrate'), array('showhidden' => true, 'multiple' => true));
        $mform->addHelpButton('disallowedcourses', 'disallowedcourses', 'tool_cbemigrate');
        $mform->addElement('date_time_selector', 'coursestartdate', get_string('startdatefrom', 'tool_cbemigrate'), array('optional' => true));
        $mform->addHelpButton('coursestartdate', 'coursestartdate', 'tool_cbemigrate');

        $this->add_action_buttons(true, get_string('performmigration', 'tool_cbemigrate'));
    }

    public function validation($data, $files) {
        $errors = array();

        if ($data['from'] == $data['to']) {
            $errors['to'] = 'Cannot migrate from and to the same framework.';

        } else {
            $mapper = new \tool_cbemigrate\framework_mapper($data['from'], $data['to']);
            $mapper->automap();
            if (!$mapper->has_mappings()) {
                $errors['to'] = 'Could not map to any competency in this framework.';
            }
        }

        return $errors;
    }

}
