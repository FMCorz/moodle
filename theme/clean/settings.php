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
 * Moodle's Clean theme, an example of how to make a Bootstrap theme
 *
 * DO NOT MODIFY THIS THEME!
 * COPY IT FIRST, THEN RENAME THE COPY AND MODIFY IT INSTEAD.
 *
 * For full information about creating Moodle themes, see:
 * http://docs.moodle.org/dev/Themes_2.0
 *
 * @package   theme_clean
 * @copyright 2013 Moodle, moodle.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // @textColor setting.
    $name = 'theme_clean/textcolor';
    $title = get_string('textcolor', 'theme_clean');
    $description = get_string('textcolor_desc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // @linkColor setting.
    $name = 'theme_clean/linkcolor';
    $title = get_string('linkcolor', 'theme_clean');
    $description = get_string('linkcolor_desc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // @bodyBackground setting.
    $name = 'theme_clean/bodybackground';
    $title = get_string('bodybackground', 'theme_clean');
    $description = get_string('bodybackground_desc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background image setting.
    $name = 'theme_clean/backgroundimage';
    $title = get_string('backgroundimage', 'theme_clean');
    $description = get_string('backgroundimage_desc', 'theme_clean');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background repeat setting.
    $name = 'theme_clean/backgroundrepeat';
    $title = get_string('backgroundrepeat', 'theme_clean');
    $description = get_string('backgroundrepeat_desc', 'theme_clean');;
    $default = '0';
    $choices = array(
        '0' => get_string('default'),
        'repeat' => get_string('backgroundrepeatrepeat', 'theme_clean'),
        'repeat-x' => get_string('backgroundrepeatrepeatx', 'theme_clean'),
        'repeat-y' => get_string('backgroundrepeatrepeaty', 'theme_clean'),
        'no-repeat' => get_string('backgroundrepeatnorepeat', 'theme_clean'),
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background position setting.
    $name = 'theme_clean/backgroundposition';
    $title = get_string('backgroundposition', 'theme_clean');
    $description = get_string('backgroundposition_desc', 'theme_clean');
    $default = '0';
    $choices = array(
        '0' => get_string('default'),
        'left_top' => get_string('backgroundpositionlefttop', 'theme_clean'),
        'left_center' => get_string('backgroundpositionleftcenter', 'theme_clean'),
        'left_bottom' => get_string('backgroundpositionleftbottom', 'theme_clean'),
        'right_top' => get_string('backgroundpositionrighttop', 'theme_clean'),
        'right_center' => get_string('backgroundpositionrightcenter', 'theme_clean'),
        'right_bottom' => get_string('backgroundpositionrightbottom', 'theme_clean'),
        'center_top' => get_string('backgroundpositioncentertop', 'theme_clean'),
        'center_center' => get_string('backgroundpositioncentercenter', 'theme_clean'),
        'center_bottom' => get_string('backgroundpositioncenterbottom', 'theme_clean'),
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background fixed setting.
    $name = 'theme_clean/backgroundfixed';
    $title = get_string('backgroundfixed', 'theme_clean');
    $description = get_string('backgroundfixed_desc', 'theme_clean');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Main content background color.
    $name = 'theme_clean/contentbackground';
    $title = get_string('contentbackground', 'theme_clean');
    $description = get_string('contentbackground_desc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Secondary background color.
    $name = 'theme_clean/secondarybackground';
    $title = get_string('secondarybackground', 'theme_clean');
    $description = get_string('secondarybackground_desc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Invert Navbar to dark background.
    $name = 'theme_clean/invert';
    $title = get_string('invert', 'theme_clean');
    $description = get_string('invertdesc', 'theme_clean');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo file setting.
    $name = 'theme_clean/logo';
    $title = get_string('logo','theme_clean');
    $description = get_string('logodesc', 'theme_clean');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom CSS file.
    $name = 'theme_clean/customcss';
    $title = get_string('customcss', 'theme_clean');
    $description = get_string('customcssdesc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footnote setting.
    $name = 'theme_clean/footnote';
    $title = get_string('footnote', 'theme_clean');
    $description = get_string('footnotedesc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
