<?php
// require_once($CFG->dirroot . '/course/renderer.php');
// class theme_clean_core_course_renderer extends core_course_renderer {
//     protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
//         return 'Coucou Babae, je suis un renderer!';
//     }
// }

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');
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

class theme_clean_core_renderer extends theme_bootstrapbase_core_renderer {

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
            if ($item->type === navigation_node::TYPE_CATEGORY) {
                $parentcat = coursecat::get($this->page->course->category)->parent;
                $parentcat = coursecat::get($parentcat);
                $neighbours = $parentcat->get_children();
                $content = array();
                foreach ($neighbours as $neighbour) {
                    if ($neighbour->id == $this->page->course->category) {
                        continue;
                    }
                    $content[] = "<a href='$CFG->wwwroot/course/index.php?categoryid={$neighbour->id}'>{$neighbour->name}</a>";
                }
                $dropdown = "<a class='dropdown-toggle' href='#' data-toggle='dropdown'><b class='caret'></b></a><ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_COURSE) {
                // $item->add_class('dropdown');
                $parentcat = coursecat::get($this->page->course->category);

                $content = array();
                $courses = enrol_get_my_courses('id');
                $neighbours = $parentcat->get_courses();
                foreach ($neighbours as $neighbour) {
                    // print_object($neighbour);
                    if ($neighbour->id === $this->page->course->id) {
                        continue;
                    } else if (!isset($courses[$neighbour->id]) && !is_siteadmin()) {
                        continue;
                    }
                    $content[] = "<a href='$CFG->wwwroot/course/view.php?id={$neighbour->id}'>{$neighbour->fullname}</a>";
                }
                $dropdown = "<a class='dropdown-toggle' href='#' data-toggle='dropdown'><b class='caret'></b></a><ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_SECTION) {
                $parentnode = $item->parent;
                $neighbours = $parentnode->children->type(navigation_node::TYPE_SECTION);
                $content = array();
                foreach ($neighbours as $neighbour) {
                    if ($neighbour->text === $item->text) {
                        continue;
                    }
                    if ($neighbour->action == null) {
                        $neighbour->action = new moodle_url('/course/view.php', array('id'=>$this->page->course->id));
                    }
                    $neighbour->icon = null;
                    $content[] = $this->render($neighbour);
                }
                $dropdown = "<a class='dropdown-toggle' href='#' data-toggle='dropdown'><b class='caret'></b></a><ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            } else if ($item->type === navigation_node::TYPE_ACTIVITY || $item->type === navigation_node::TYPE_RESOURCE) {
                $cm = $this->page->cm;
                $course = $cm->get_modinfo();
                $section = $course->get_section_info($cm->sectionnum);
                $content = array();
                foreach ($course->get_cms() as $ccm) {
                    if ($section->section != $ccm->sectionnum) {
                        continue;
                    }
                    $content[] = html_writer::link($ccm->get_url(), $ccm->get_formatted_name());
                }
                $dropdown = "<a class='dropdown-toggle' href='#' data-toggle='dropdown'><b class='caret'></b></a><ul class='dropdown-menu'><li>" . implode("</li><li>", $content) . "</li></ul>";
            }
            $renderered = $this->render($item);
            $renderered .= $dropdown;
            $breadcrumbs[] = $renderered;

        }
        $divider = '<span class="divider">/</span>';
        $list_items = '<li class="dropdown">'.join(" $divider</li><li class='dropdown'>", $breadcrumbs).'</li>';
        $title = '<span class="accesshide">'.get_string('pagepath').'</span>';
        return $title . "<ul class=\"breadcrumb\">$list_items</ul>";
    }

}