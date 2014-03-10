<?php
define('MOODLE_INTERNAL', true);

require_once('config.php');
require_once($CFG->libdir . '/formslib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Form test');
$PAGE->set_url(new moodle_url('/test_forms.php'));

class mystupidform extends moodleform {
    var $error = false;
    var $errorfield = null;
    function definition() {}

    function add_action_buttons($cancel = true, $submitlabel=null) {
        $mform =& $this->_form;
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'error', 'Error');
        $buttonarray[] = &$mform->createElement('submit', 'submit', 'Save');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    function create_error() {
        $this->error = true;
    }
    function validation($data, $files) {
        if ($this->error) {
            return array((string) $this->errorfield => 'Ouch! This is bad, really bad...');
        }
        return array();
    }
}

class weirdsection_form extends mystupidform {
    var $errorfield = 'weirdsection_form_name';
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('listing', 'test12345', 'Smart select', array(
            (object) array('rowhtml' => 'ROW', 'mainhtml' => 'MAIN'),
            (object) array('rowhtml' => 'ROW', 'mainhtml' => 'MAIN'),
            (object) array('rowhtml' => 'ROW', 'mainhtml' => 'MAIN'),
            (object) array('rowhtml' => 'ROW', 'mainhtml' => 'MAIN'),
            (object) array('rowhtml' => 'ROW', 'mainhtml' => 'MAIN'),
        ));

        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->addElement('text', get_class($this).'_address', 'Street name');
        $mform->setType(get_class($this).'_address', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_zip', 'Zip code');
        $mform->setType(get_class($this).'_zip', PARAM_RAW);
        $this->add_action_buttons();
    }
}

class onesection_form extends mystupidform {
    var $errorfield = 'onesection_form_name';
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $this->add_action_buttons();
    }
}

class twosections_form extends mystupidform {
    var $errorfield = 'twosections_form_address';
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_sndsection', 'Second section');
        $mform->addElement('text', get_class($this).'_address', 'Street name');
        $mform->setType(get_class($this).'_address', PARAM_RAW);
        // $mform->addElement('text', get_class($this).'_zip', 'Zip code');
        $mform->setType(get_class($this).'_zip', PARAM_RAW);
        $this->add_action_buttons();
    }
}

class twosections_collapsed_form extends mystupidform {
    var $errorfield = 'twosections_collapsed_form_address';
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_sndsection', 'Second section');
        $mform->setExpanded(get_class($this).'_sndsection', false);
        $mform->addElement('text', get_class($this).'_address', 'Street name');
        $mform->setType(get_class($this).'_address', PARAM_RAW);
        // $mform->addElement('text', get_class($this).'_zip', 'Zip code');
        $mform->setType(get_class($this).'_zip', PARAM_RAW);
        $this->add_action_buttons();
    }
}

class twosections_collapsed_form_but_required extends mystupidform {
    var $errorfield = 'twosections_collapsed_form_but_required_address';
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_sndsection', 'Second section');
        $mform->setExpanded(get_class($this).'_sndsection', false);
        $mform->addElement('text', get_class($this).'_address', 'Street name');
        $mform->setType(get_class($this).'_address', PARAM_RAW);
        $mform->addRule(get_class($this).'_address', 'What have you done again?', 'required');
        // $mform->addElement('text', get_class($this).'_zip', 'Zip code');
        $mform->setType(get_class($this).'_zip', PARAM_RAW);
        $this->add_action_buttons();
    }
}

class moresections_form extends mystupidform {
    var $errorfield = 'moresections_form_issue';
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_sndsection', 'Second section');
        $mform->addElement('text', get_class($this).'_address', 'Street name');
        $mform->setType(get_class($this).'_address', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_zip', 'Zip code');
        $mform->setType(get_class($this).'_zip', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_trdsection', 'Third section');
        $mform->addElement('text', get_class($this).'_issue', 'Issue number');
        $mform->setType(get_class($this).'_issue', PARAM_RAW);
        $mform->setAdvanced(get_class($this).'_issue');
        $mform->addElement('text', get_class($this).'_resolution', 'Resolution');
        $mform->setType(get_class($this).'_resolution', PARAM_RAW);
        $this->add_action_buttons();
    }
}

class funkysections_form extends mystupidform {
    var $errorfield = 'funkysections_form_name';
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', get_class($this).'_firstsection', 'First section');
        $mform->setExpanded(get_class($this).'_firstsection', false);
        $mform->addElement('text', get_class($this).'_name', 'Name');
        $mform->setType(get_class($this).'_name', PARAM_RAW);
        $mform->setAdvanced(get_class($this).'_name');
        $mform->addElement('text', get_class($this).'_first', 'First name');
        $mform->setType(get_class($this).'_first', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_sndsection', 'Second section');
        $mform->setExpanded(get_class($this).'_sndsection', true);
        $mform->addElement('text', get_class($this).'_address', 'Street name');
        $mform->setType(get_class($this).'_address', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_zip', 'Zip code');
        $mform->setType(get_class($this).'_zip', PARAM_RAW);
        $mform->addElement('header', get_class($this).'_trdsection', 'Third section');
        $mform->addElement('text', get_class($this).'_issue', 'Issue number');
        $mform->setType(get_class($this).'_issue', PARAM_RAW);
        $mform->addElement('text', get_class($this).'_resolution', 'Resolution');
        $mform->setType(get_class($this).'_resolution', PARAM_RAW);
        $mform->setAdvanced(get_class($this).'_resolution');
        $mform->addElement('text', get_class($this).'_assignee', 'Assignee');
        $mform->setType(get_class($this).'_assignee', PARAM_RAW);
        $mform->setAdvanced(get_class($this).'_assignee');
        $this->add_action_buttons();
    }
}

$classes = array(
    'weirdsection_form',
    'onesection_form',
    'twosections_form',
    'twosections_collapsed_form',
    'twosections_collapsed_form_but_required',
    'moresections_form',
    'funkysections_form',
);

echo $OUTPUT->header();
foreach ($classes as $class) {
    $form = new $class();
    echo '<h1>' . $class . '</h1>';
    if ($form->is_submitted() && $data = $form->get_submitted_data()) {
        if (isset($data->error)) {
            $form->create_error();
        }
        $form->get_data();
    }
    $form->display();
}
echo $OUTPUT->footer();
