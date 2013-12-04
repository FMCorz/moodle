<?php
// require_once($CFG->dirroot . '/course/renderer.php');
// class theme_clean_core_course_renderer extends core_course_renderer {
//     protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
//         return 'Coucou Babae, je suis un renderer!';
//     }
// }

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');
require_once($CFG->libdir . '/coursecatlib.php');


class theme_clean_core_renderer extends theme_bootstrapbase_core_renderer {

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
        $data->loggedinas = \core\session\manager::is_loggedinas();
        $data->realuser = \core\session\manager::get_realuser();
        $data->loginasfullname = fullname($data->realuser, true);
        if (\core\session\manager::is_loggedinas()) {
            // $realuser = \core\session\manager::get_realuser();
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
                <ul class='nav'>
                    <?php if (false && $this->page->course->id != SITEID): ?>
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
                    <?php if (false): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="position: relative;"><?php echo $this->pix_icon('t/message', '', '', array('style' => 'padding-right: 5px')); ?><b class="caret"></b></a>
                        <ul class="dropdown-menu pull-right">
                            <li><?php echo html_writer::link(new moodle_url('/message/index.php'), $this->pix_icon('t/message', '') . ' Messages'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/blog/index.php', array('userid' => $USER->id)), 'Blog'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/mod/forum/user.php', array('id' => $USER->id)), 'Forum posts'); ?></li>
                        </ul>
                    </li>
                    <?php endif ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding: 0 3px; text-decoration: none; display: block; line-height: 41px; height: 41px; position: relative;"><?php echo fullname($USER, true); ?>
                            <b class="caret" style='vertical-align: text-top;'></b> <?php echo $this->user_picture($USER, array('link' => false)); ?></a>
                        <ul class="dropdown-menu pull-right">
                            <li><?php echo html_writer::link(new moodle_url('/my/'), $this->pix_icon('i/course', '') . ' My dashboard'); ?></li>
                            <li class="divider"></li>
                            <li><?php echo html_writer::link($data->link, $this->pix_icon('i/user', '') . ' My profile'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/message/index.php'), $this->pix_icon('t/message', '') . ' Messages'); ?></li>
                            <li><?php echo html_writer::link(new moodle_url('/grade/report/overview/index.php', array('id' => 1, 'userid' => $USER->id)), $this->pix_icon('i/grades', '') . ' My grades'); ?></li>
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