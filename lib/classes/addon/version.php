<?php
namespace core\addon;

class version implements version_interface {

    protected $version;
    protected $maturity;
    protected $release;
    protected $requires;

    public function __construct($version, $maturity, $release, $requires) {
        // TODO Add validation.
        $this->version = $version;
        $this->maturity = $maturity;
        $this->release = $release;
    }

    public function get_version() {
        return $this->version;
    }

    public function get_maturity() {
        return $this->maturity;
    }

    public function get_release_name() {
        return $this->release;
    }

    public function get_required_version() {
        return $this->requires;
    }

}
