<?php
// require_once($CFG->dirroot . '/course/renderer.php');
// class theme_clean_core_course_renderer extends core_course_renderer {
//     protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
//         return 'Coucou Babae, je suis un renderer!';
//     }
// }

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');
require_once($CFG->libdir . '/coursecatlib.php');

class theme_clean_block_navigation_renderer extends block_navigation_renderer {

    protected function navigation_node($items, $attrs=array(), $expansionlimit=null, array $options = array(), $depth=1) {
        // $items = (array) clone((object) $items);
        foreach ($items as $key => $col) {
            $col = $col->children;
            foreach ($col->get_key_list() as $key) {
                $item = $col->get($key);
                if (in_array($item->key, array('currentcourse', 'mycourses', 'courses'))) {
                    // $col->remove($key);
                }
            }
        }
        return parent::navigation_node($items, $attrs, $expansionlimit, $options, $depth);
    }

}

require_once($CFG->dirroot . '/mod/lesson/renderer.php');
class theme_clean_mod_lesson_renderer extends mod_lesson_renderer {

    public function header($lesson, $cm, $currenttab = '', $extraeditbuttons = false, $lessonpageid = null, $extrapagetitle = null) {
        global $CFG;

        $activityname = format_string($lesson->name, true, $lesson->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname.": ".$activityname;
        } else {
            $title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
        }

        // Build the buttons
        $context = context_module::instance($cm->id);

    /// Header setup
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        lesson_add_header_buttons($cm, $context, $extraeditbuttons, $lessonpageid);
        $output = $this->output->header();

        if (has_capability('mod/lesson:manage', $context)) {
            // $output .= $this->output->heading_with_help($activityname, 'overview', 'lesson');

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/lesson/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            // $output .= $this->output->heading($activityname);
        }

        foreach ($lesson->messages as $message) {
            $output .= $this->output->notification($message[0], $message[1], $message[2]);
        }

        return $output;
    }

}

require_once($CFG->dirroot . '/mod/assign/renderer.php');
class theme_clean_mod_assign_renderer extends mod_assign_renderer {
    /**
     * Render the header.
     *
     * @param assign_header $header
     * @return string
     */
    public function render_assign_header(assign_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'assign'));
        $this->page->set_heading($header->assign->name);

        $o .= $this->output->header();
        if ($header->preface) {
            $o .= $header->preface;
        }
        // $heading = format_string($header->assign->name, false, array('context' => $header->context));
        // $o .= $this->output->heading($heading);

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            $o .= $this->output->box_end();
        }

        return $o;
    }
}

class theme_clean_core_renderer extends theme_bootstrapbase_core_renderer {

    public function main_content() {
        $html = '<div role="main">';
        if ($this->page->cm) {
            $nav = $this->page->settingsnav;
            // Using course admin because a student doesn't have access to it.
            if ($this->page->settingsnav->get('courseadmin')
                    && $this->page->settingsnav->get('courseadmin')->children
                    && $this->page->settingsnav->get('courseadmin')->children->count() > 0) {
                $parent = $nav->get('modulesettingskey');
                ob_start();
                ?>
                <div class="well dropdown well-small" style="float: left; margin-right: 10px; ">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <?php echo $this->pix_icon('i/settings', ''); ?>
                    <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php foreach ($parent->children as $node): ?>
                            <?php $node->icon = null; ?>
                            <li><?php echo html_writer::link($node->action, $node->get_content()); ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
                <?php
                $html .= ob_get_clean();
            }
            $html .= $this->heading($this->page->cm->name, 2);
        }
        $html .= $this->unique_main_content_token;
        $html .= '</div>';
        return $html;
    }

    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        global $CFG, $OUTPUT;

        $items = $this->page->navbar->get_items();
        $breadcrumbs = array();
        foreach ($items as $item) {
            // print_object($item);
            $item->hideicon = true;
            $dropdown = '';
            if ($item->type === navigation_node::TYPE_ROOTNODE) {
                $parentcat = $item->parent;
                $neighbours = $parentcat->get_rootnodes();
                $content = array();
                foreach ($neighbours as $neighbour) {
                    $current = '';
                    $neighbour->icon = null;
                    if (empty($neighbour->action)) {
                        continue;
                    } else if ($neighbour->text == $item->text) {
                        $neighbour->add_class('current');
                    }
                    $content[] = $this->render($neighbour);
                }
                $dropdown = "<ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_CATEGORY) {
                $parentcat = coursecat::get($this->page->course->category)->parent;
                $parentcat = coursecat::get($parentcat);
                $neighbours = $parentcat->get_children();
                $content = array();
                foreach ($neighbours as $neighbour) {
                    $current = '';
                    if ($neighbour->id == $this->page->course->category) {
                        $current = 'current';
                    }
                    $content[] = "<a class='$current' href='$CFG->wwwroot/course/index.php?categoryid={$neighbour->id}'>{$neighbour->name}</a>";
                }
                $dropdown = "<ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_COURSE) {
                // $item->add_class('dropdown');
                $parentcat = coursecat::get($this->page->course->category);

                $content = array();
                $courses = enrol_get_my_courses('id');
                $neighbours = $parentcat->get_courses();
                foreach ($neighbours as $neighbour) {
                    $current = '';
                    if ($neighbour->id === $this->page->course->id) {
                        $current = 'current';
                    } else if (!isset($courses[$neighbour->id]) && !is_siteadmin()) {
                        continue;
                    }
                    $content[] = "<a class='$current' href='$CFG->wwwroot/course/view.php?id={$neighbour->id}'>{$neighbour->fullname}</a>";
                }
                $dropdown = "<ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_SECTION) {
                $parentnode = $item->parent;
                $neighbours = $parentnode->children->type(navigation_node::TYPE_SECTION);
                $content = array();
                foreach ($neighbours as $neighbour) {
                    $current = '';
                    if ($neighbour->text == $item->text) {
                        $neighbour->add_class('current');
                    }
                    if ($neighbour->action == null) {
                        $neighbour->action = new moodle_url('/course/view.php', array('id'=>$this->page->course->id));
                    }
                    $neighbour->icon = null;
                    $content[] = $this->render($neighbour);
                }
                $dropdown = "<ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_ACTIVITY || $item->type === navigation_node::TYPE_RESOURCE) {
                $cm = $this->page->cm;
                $course = $cm->get_modinfo();
                $section = $course->get_section_info($cm->sectionnum);
                $content = array();
                foreach ($course->get_cms() as $ccm) {
                    $current = '';
                    if ($section->section != $ccm->sectionnum) {
                        continue;
                    } else if (!$ccm->has_view()) {
                        continue;
                    } else if (!$ccm->uservisible) {
                        continue;
                    } else if ($ccm->id == $cm->id) {
                        $current = 'current';
                    }
                    $content[] = html_writer::link($ccm->get_url(), html_writer::empty_tag('img', array('src' => $ccm->get_icon_url())) .
                        ' ' . $ccm->get_formatted_name(), array('class' => $current));
                }
                $dropdown = "<ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_SETTING && !empty($item->action)) {
                $parentnode = $item->parent;
                $neighbours = $parentnode->children->type(navigation_node::TYPE_SETTING);
                $content = array();
                foreach ($neighbours as $neighbour) {
                    $current = '';
                    if ($neighbour->text == $item->text) {
                        $neighbour->add_class('current');
                    }
                    // if ($neighbour->action == null) {
                    //     $neighbour->action = new moodle_url('/course/view.php', array('id'=>$this->page->course->id));
                    // }
                    $neighbour->icon = null;
                    $content[] = $this->render($neighbour);
                }
                $dropdown = "<ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            }

            $renderered = $item->get_content();
            if (!empty($item->action)) {
                $renderered = html_writer::link($item->action, $item->get_content());
            }

            if (!empty($dropdown)) {
                // $renderered = html_writer::link($item->action, $item->get_content() . '<b class="caret"></b>', array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'));
                $renderered .= ' ' . html_writer::link('#', '<b class="caret"></b>', array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'));
                $renderered .= $dropdown;
            }

            $breadcrumbs[] = $renderered;

        }

        if (empty($breadcrumbs)) {
            $breadcrumbs[] = html_writer::link(new moodle_url('/'), 'Home');
        }

        $divider = '<span class="divider">/</span>';
        $list_items = '<li class="dropdown">'.join(" $divider</li><li class='dropdown'>", $breadcrumbs).'</li>';
        $title = '<span class="accesshide">'.get_string('pagepath').'</span>';
        return $title . "<ul class=\"breadcrumb\">$list_items</ul>";
    }

    /**
     * Return the standard string that says whether you are logged in (and switched
     * roles/logged in as another user).
     * @param bool $withlinks if false, then don't include any links in the HTML produced.
     * If not set, the default is the nologinlinks option from the theme config.php file,
     * and if that is not set, then links are included.
     * @return string HTML fragment.
     */
    public function login_info($withlinks = null) {
        global $USER, $CFG, $DB, $SESSION;

        $data = array(
            'withlinks' => null,
            'withlinks' => null,
            'loginpage' => null,
            'course' => null,
            'loggedinas' => null,
            'realuser' => null,
            'loginasfullname' => null,
            'realuser' => null,
            'withlinks' => null,
            'loginastitle' => null,
            'loginaslink' => null,
            'course' => null,
            'loginurl' => null,
            'context' => null,
            'fullname' => null,
            'loggedinasguest' => null,
            'withlinks' => null,
            'link' => null,
            'linktitle' => null,
            'from' => null,
            'fromurl' => null,
            'loginas' => null,
            'loginpage' => null,
            'withlinks' => null,
            'showloginlink' => null,
            'role' => null,
            'loginas' => null,
            'username' => null,
            'switchrole' => null,
            'switchroleurl' => null,
            'switchroleurl' => null,
            'switchroleurl' => null,
            'loginas' => null,
            'username' => null,
            'showlogout' => null,
            'logouttext' => null,
            'notloggedin' => null,
            'loginas' => null,
            'loginpage' => null,
            'withlinks' => null,
            'showloginlink' => null,
            'logintext' => null,
            'notloggedin' => null,
            'loginas' => null,
            'showloginlink' => null,
            'loginurl' => null,
            'logintext' => null,
            'username' => null
        );
        $data = (object) $data;

        if (during_initial_install()) {
            return '';
        }

        $data->withlinks = $withlinks;
        if (is_null($withlinks)) {
            $data->withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        $data->loginpage = ((string)$this->page->url === get_login_url());
        $data->course = $this->page->course;
        $course = $this->page->course;
        $data->loggedinas = session_is_loggedinas();
        $data->realuser = session_get_realuser();
        $data->loginasfullname = fullname($data->realuser, true);
        if (session_is_loggedinas()) {
            // $realuser = session_get_realuser();
            // $fullname = fullname($realuser, true);
            if ($data->withlinks) {
                $data->loginastitle = get_string('loginas');
                $data->loginaslink = new moodle_url('/course/loginas.php', array('id' => $data->course->id, 'sesskey' => sesskey()));
                // $loginastitle = get_string('loginas');
                // $realuserinfo = " [<a href=\"$CFG->wwwroot/course/loginas.php?id=$course->id&amp;sesskey=".sesskey()."\"";
                // $realuserinfo .= "title =\"".$loginastitle."\">$fullname</a>] ";
            } else {
                // $realuserinfo = " [$fullname] ";
            }
        } else {
            $realuserinfo = '';
        }

        $loginurl = get_login_url();
        $data->loginurl = $loginurl;

        if (empty($course->id)) {
            // $course->id is not defined during installation
            return '';
        } else if (isloggedin()) {
            $context = context_course::instance($course->id);
            $data->context = $context;

            $fullname = fullname($USER, true);
            $data->fullname = $fullname;
            // Since Moodle 2.0 this link always goes to the public profile page (not the course profile page)
            if ($data->withlinks) {
                $data->link = new moodle_url('/user/profile.php', array('id' => $USER->id));
                $data->linktitle = get_string('viewprofile');
                // $linktitle = get_string('viewprofile');
                // $username = "<a href=\"$CFG->wwwroot/user/profile.php?id=$USER->id\" title=\"$linktitle\">$fullname</a>";
            } else {
                // $username = $fullname;
            }
            if (is_mnet_remote_user($USER) and $idprovider = $DB->get_record('mnet_host', array('id'=>$USER->mnethostid))) {
                $data->from = $idprovider->name;
                if ($withlinks) {
                    $data->fromurl = $idprovider->wwwroot;
                    // $username .= " from <a href=\"{$idprovider->wwwroot}\">{$idprovider->name}</a>";
                } else {
                    // $username .= " from {$idprovider->name}";
                }
            }
            if (isguestuser()) {
                $loggedinas = $realuserinfo.get_string('loggedinasguest');
                $data->loggedinasguest = true;
                $data->loginas = get_string('loggedinasguest');
                if (!$data->loginpage && $data->withlinks) {
                    $data->showloginlink = true;
                    // $loggedinas .= " (<a href=\"$loginurl\">".get_string('login').'</a>)';
                }
            } else if (is_role_switched($course->id)) { // Has switched roles
                $rolename = '';
                if ($role = $DB->get_record('role', array('id'=>$USER->access['rsw'][$context->path]))) {
                    $data->role = role_get_name($role, $context);
                    $rolename = ': '.role_get_name($role, $context);
                }
                // $loggedinas = get_string('loggedinas', 'moodle', $username).$rolename;
                $data->loginas = get_string('loggedinas', 'moodle', $data->fullname);
                if ($withlinks) {
                    $data->switchrole = get_string('switchrolereturn');
                    $data->switchroleurl = new moodle_url('/course/switchrole.php', array('id'=>$course->id,'sesskey'=>sesskey(), 'switchrole'=>0, 'returnurl'=>$this->page->url->out_as_local_url(false)));
                    $data->switchroleurl = $data->switchroleurl->out();
                    // $loggedinas .= '('.html_writer::tag('a', get_string('switchrolereturn'), array('href'=>$url)).')';
                }
            } else {
                // $loggedinas = $realuserinfo.get_string('loggedinas', 'moodle', $username);
                $data->loginas = get_string('loggedinas', 'moodle', $data->fullname);
                if ($data->withlinks) {
                    $data->showlogout = true;
                    $data->logouttext = get_string('logout');
                    $data->logouturl = new moodle_url('/login/logout.php', array('sesskey' => sesskey()));
                    // $loggedinas .= " (<a href=\"$CFG->wwwroot/login/logout.php?sesskey=".sesskey()."\">".get_string('logout').'</a>)';
                }
            }
        } else {
            $data->notloggedin = true;
            $data->loginas = get_string('loggedinnot', 'moodle');
            if (!$data->loginpage && $data->withlinks) {
                $data->showloginlink = true;
            }
        }
        $data->logintext = get_string('login');

        ob_start();
        ?>
        <div class='logininfo'>
            <?php if ($data->notloggedin || $data->loggedinasguest): ?>
                <?php echo $data->loginas; ?>
                <?php if ($data->showloginlink): ?>
                    <?php echo html_writer::link($data->loginurl, $data->logintext); ?>
                <?php endif ?>
            <?php else: ?>
                <ul>
                    <?php if ($this->page->course->id != SITEID): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="position: relative;"><?php echo $this->pix_icon('i/course', '', '', array('style' => 'padding-right: 5px')); ?><?php echo $this->page->course->shortname; ?><b class="caret"></b></a>
                            <ul class="dropdown-menu pull-right">
                                <li><?php echo html_writer::link(new moodle_url('/course/view.php', array('id' => $this->page->course->id)), 'Course home'); ?>
                                <li class="divider"></li>
                                <li><?php echo html_writer::link(new moodle_url('/user/index.php', array('id' => $this->page->course->id)), $this->pix_icon('i/users', '') . ' Participants'); ?>
                                <!-- <li><?php echo html_writer::link(new moodle_url('/badges/view.php', array('type' => 2, 'id' => $this->page->course->id)), $this->pix_icon('i/badge', '') . ' Course badges'); ?> -->

                                <?php if (has_capability('moodle/grade:viewall', $this->page->context)): ?>
                                    <li><?php echo html_writer::link(new moodle_url('/grade/report/index.php', array('id' => $this->page->course->id)), $this->pix_icon('i/grades', '') . ' Grades'); ?>
                                <?php else: ?>
                                    <li><?php echo html_writer::link(new moodle_url('/grade/report/user/index.php', array('id' => $this->page->course->id, 'userid' => $USER->id)), $this->pix_icon('i/grades', '') . ' Grades'); ?>
                                <?php endif ?>

                                <?php if (has_capability('moodle/site:viewreports', $this->page->context)): ?>
                                    <li><?php echo html_writer::link(new moodle_url('/report/outline/index.php', array('id' => $this->page->course->id)), $this->pix_icon('i/report', '') . ' Reports'); ?>
                                <?php endif ?>

                                <?php if ($this->page->settingsnav->get('courseadmin')
                                        && $this->page->settingsnav->get('courseadmin')->children
                                        && $this->page->settingsnav->get('courseadmin')->children->count() > 0): ?>
                                    <li class="divider"></li>
                                    <li><?php echo html_writer::link(new moodle_url('/course/preferences.php', array('id' => $this->page->course->id)), $this->pix_icon('i/settings', '') . ' Preferences'); ?>
                                <?php endif ?>
                            </ul>
                        </li>
                    <?php endif ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="position: relative;"><?php echo $this->pix_icon('t/message', '', '', array('style' => 'padding-right: 5px')); ?><b class="caret"></b></a>
                        <ul class="dropdown-menu pull-right">
                            <li><?php echo html_writer::link(new moodle_url('/message/index.php'), $this->pix_icon('t/message', '') . ' Messages'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/blog/index.php', array('userid' => $USER->id)), 'Blog'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/mod/forum/user.php', array('id' => $USER->id)), 'Forum posts'); ?></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="position: relative;"><?php echo $this->pix_icon('i/user', ''); ?><b class="caret"></b></a>
                        <ul class="dropdown-menu pull-right">
                            <li><?php echo html_writer::link($data->link, $data->fullname); ?></li>
                            <li class="divider"></li>
                            <li><?php echo html_writer::link(new moodle_url('/grade/report/overview/index.php', array('id' => 1, 'userid' => $USER->id)), $this->pix_icon('i/grades', '') . ' My grades'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/badges/mybadges.php'), $this->pix_icon('i/badge', '') . ' My badges'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/user/files.php'), $this->pix_icon('i/files', '') . ' Private files'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/user/preferences.php'), $this->pix_icon('i/settings', '') . ' Preferences'); ?></li>
                            <?php if ($data->showlogout): ?>
                                <li class="divider"></li>
                                <li><?php echo html_writer::link($data->logouturl, $data->logouttext); ?></li>
                            <?php endif ?>
                        </ul>
                    </li>
                </ul>
                <div class="dropdown">

                </div>
            <?php endif ?>
        </div>
        <?php
        $output = ob_get_clean();
        return $output;

        // $loggedinas = '<div class="logininfo">'.$loggedinas.'</div>';

        // if (isset($SESSION->justloggedin)) {
        //     unset($SESSION->justloggedin);
        //     if (!empty($CFG->displayloginfailures)) {
        //         if (!isguestuser()) {
        //             if ($count = count_login_failures($CFG->displayloginfailures, $USER->username, $USER->lastlogin)) {
        //                 $loggedinas .= '&nbsp;<div class="loginfailures">';
        //                 if (empty($count->accounts)) {
        //                     $loggedinas .= get_string('failedloginattempts', '', $count);
        //                 } else {
        //                     $loggedinas .= get_string('failedloginattemptsall', '', $count);
        //                 }
        //                 if (file_exists("$CFG->dirroot/report/log/index.php") and has_capability('report/log:view', context_system::instance())) {
        //                     $loggedinas .= ' (<a href="'.$CFG->wwwroot.'/report/log/index.php'.
        //                                          '?chooselog=1&amp;id=1&amp;modid=site_errors">'.get_string('logs').'</a>)';
        //                 }
        //                 $loggedinas .= '</div>';
        //             }
        //         }
        //     }
        // }

        return $loggedinas;
    }

}