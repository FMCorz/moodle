<?php

require(__DIR__ . '/../config.php');

$courseid = optional_param('id', SITEID, PARAM_INT);
require_login($courseid);

$PAGE->set_pagelayout('standard');
$PAGE->set_title('Preferences');
$PAGE->set_url(new moodle_url('/course/preferences.php', array('id' => $courseid)));

echo $OUTPUT->header();

$nav = $PAGE->settingsnav->get('courseadmin');

echo html_writer::start_tag('div', array('class' => 'row-fluid'));
$i = 1;
foreach ($nav->children as $node) {

    echo html_writer::start_tag('div', array('class' => 'span4'));
    echo html_writer::start_tag('div');
    echo html_writer::tag('h3', $node->get_content());
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div');
    echo html_writer::start_tag('ul');
    foreach ($node->children as $child) {
        $content = $child->get_content();
        if (empty($content)) {
            continue;
        }
        echo html_writer::tag('li', html_writer::link($child->action, $content));
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    if ($i++ % 3 == 0) {
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', array('class' => 'row-fluid'));
    }
}


echo html_writer::end_tag('div');

echo $OUTPUT->footer();
