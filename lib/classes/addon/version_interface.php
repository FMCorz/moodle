<?php
namespace core\addon;

interface version_interface {

    public function get_version();
    public function get_maturity();
    public function get_release_name();
    public function get_required_version();

}
