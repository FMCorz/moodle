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
 * Defines the addon type.
 *
 * @package    core_addon
 * @copyright  2017 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\plugininfo;

use core_plugin_manager;
use moodle_url;
use part_of_admin_tree;
use admin_settingpage;

defined('MOODLE_INTERNAL') || die();


/**
 * Class addon.
 *
 * @package    core_addon
 * @copyright  2017 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addon extends base {

    public static function get_enabled_plugins() {
        return core_plugin_manager::instance()->get_installed_plugins('addon');
    }

    /**
     * Return the node name to use in admin settings menu for this plugin.
     *
     * @return string node name
     */
    public function get_settings_section_name() {
        return 'addon' . $this->name;
    }

    /**
     * Loads plugin settings to the settings tree
     *
     * This function usually includes settings.php file in plugins folder.
     * Alternatively it can create a link to some settings page (instance of admin_externalpage)
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig whether the current user has moodle/site:config capability
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        // global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        // $ADMIN = $adminroot; // May be used in settings.php.
        // $plugininfo = $this; // Also can be used inside settings.php.
        // $antivirus = $this;  // Also can be used inside settings.php.

        // if (!$this->is_installed_and_upgraded()) {
        //     return;
        // }

        // if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
        //     return;
        // }

        // $section = $this->get_settings_section_name();

        // $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        // include($this->full_path('settings.php')); // This may also set $settings to null.

        // if ($settings) {
        //     $ADMIN->add($parentnodename, $settings);
        // }
    }

}
