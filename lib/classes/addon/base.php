<?php
namespace core\addon;

abstract class base implements addon_interface {

    public function get_component() {
        return 'addon_' . $this->get_codename();
    }

    public function get_folder() {
        return $CFG->dirroot . '/addon/' . $this->get_codename();
    }

}
