<?php

namespace addon_helloworld;

use core\addon\base;
use core\addon\version;

class addon extends base {

    public function get_codename() {
        return 'helloworld';
    }

    public function get_name() {
        return 'Hello World!';
    }

    public function get_version() {
        return new version(
            2017052400,
            MATURITY_STABLE,
            '1.0.0',
            2017051500
        );
    }

}
