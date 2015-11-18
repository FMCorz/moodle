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
 * Create plans from a template.
 *
 * @package    tool_lp
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);
$pagecontextid = required_param('pagecontextid', PARAM_INT);  // Reference to the context we came from.
$action = optional_param('action', null, PARAM_ALPHA);

require_login(0, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$pagecontext = context::instance_by_id($pagecontextid);
$template = \tool_lp\api::read_template($id);
$context = $template->get_context();
require_capability('tool/lp:templatemanage', $context);

// Set up the page.
$url = new moodle_url('/admin/tool/lp/template_assign_users.php', array(
    'id' => $id,
    'pagecontextid' => $pagecontextid
));
$templatesurl = new moodle_url('/admin/tool/lp/learningplans.php', array('pagecontextid' => $pagecontextid));

$PAGE->navigation->override_active_url($templatesurl);
$PAGE->set_context($pagecontext);

$title = get_string('assignusers', 'tool_lp');
$templatename = format_string($template->get_shortname(), true, array('context' => $context));

$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($templatename);
$PAGE->navbar->add($templatename, $url);

// Get the user_selector we will need.
$selected = new \tool_lp\form\template_user_selected('templateselected', array('accesscontext' => $pagecontext));
$selector = new \tool_lp\form\template_user_selector('templateselector', array('accesscontext' => $pagecontext));

// Apply the actions.
if ($action == 'cancel') {
    require_sesskey();

    $selected->purge_users();
    redirect($templatesurl);

} else if ($action === 'save') {
    require_sesskey();

    $count = 0;
    $errors = 0;
    $users = $selected->get_users();
    foreach ($users as $user) {
        try {
            $result = \tool_lp\api::create_plan_from_template($template, $user->id);
            if ($result !== false) {
                $count++;
            }
        } catch (Exception $e) {
            $errors++;
        }
    }

    $selected->purge_users();
    redirect($templatesurl, sprintf('A total of %s user plans where created, %s were not.', $count, $errors));

} else if ($action === 'add') {
    require_sesskey();
    $userstoadd = $selector->get_selected_users();
    if (!empty($userstoadd)) {
        $selected->add_users($userstoadd);

        $selector->invalidate_selected_users();
        $selected->invalidate_selected_users();
    }

} else if ($action === 'remove') {
    require_sesskey();

    $userstoremove = $selected->get_selected_users();
    if (!empty($userstoremove)) {
        $selected->remove_users($userstoremove);

        $selector->invalidate_selected_users();
        $selected->invalidate_selected_users();
    }

} else if ($action === null) {
    // We're new here, let's purge the list of selected users.
    $selected->purge_users();
    $selected->invalidate_selected_users();
}

// Display the page.
$output = $PAGE->get_renderer('tool_lp');
echo $output->header();
echo $output->heading($title);

// Print the form.
?>
<form id="templateassignusersform" method="post" action="<?php echo $PAGE->url ?>">
    <div>
      <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
      <input type="hidden" name="pagecontextid" value="<?php echo $pagecontextid ?>" />
      <input type="hidden" name="id" value="<?php echo $id ?>" />

      <table summary="" class="generaltable generalbox boxaligncenter" cellspacing="0">
        <tr>
          <td id="existingcell">
              <p><label for="templateselected">Selected users</label></p>
              <?php $selected->display() ?>
          </td>
          <td id="buttonscell">
              <div id="addcontrols">
                <button type="submit" value="add" name="action">
                    <?php echo $OUTPUT->larrow().'&nbsp;'.s(get_string('select')); ?>
                </button>
              </div>
              <div id="removecontrols">
                <button type="submit" value="remove" name="action">
                    <?php echo s(get_string('remove')).'&nbsp;'.$OUTPUT->rarrow(); ?>
                </button>
              </div>
          </td>
          <td id="potentialcell">
              <p><label for="templateselector">Potential users</label></p>
              <?php $selector->display() ?>
          </td>
        </tr>
      </table>
    </div>

    <div>
        <button name="action" value="cancel" type="submit" class="btn">Cancel</button>
        <button name="action" value="save" type="submit" class="btn btn-primary" <?php echo $selected->has_users() ? '' : 'disabled="disabled"' ?>>
            Create plans for the users
        </button>
    </div>

</form>


<?php

// echo $output->render($page);
echo $output->footer();
