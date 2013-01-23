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
 * Repository API unit tests
 *
 * @package   repository
 * @category  phpunit
 * @copyright 2012 Dongsheng Cai {@link http://dongsheng.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/repository/lib.php");

class repositorylib_testcase extends advanced_testcase {

    /**
     * Installing repository tests
     *
     * @copyright 2012 Dongsheng Cai {@link http://dongsheng.org}
     */
    public function test_install_repository() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $syscontext = context_system::instance();
        $repositorypluginname = 'boxnet';
        // override repository permission
        $capability = 'repository/' . $repositorypluginname . ':view';
        $allroles = $DB->get_records_menu('role', array(), 'id', 'archetype, id');
        assign_capability($capability, CAP_ALLOW, $allroles['guest'], $syscontext->id, true);

        $plugintype = new repository_type($repositorypluginname);
        $pluginid = $plugintype->create(false);
        $this->assertInternalType('int', $pluginid);
        $args = array();
        $args['type'] = $repositorypluginname;
        $repos = repository::get_instances($args);
        $repository = reset($repos);
        $this->assertInstanceOf('repository', $repository);
        $info = $repository->get_meta();
        $this->assertEquals($repositorypluginname, $info->type);
    }

    public function test_get_unused_filename() {
        global $USER;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        $fs = get_file_storage();

        $draftitemid = null;
        $context = context_user::instance($USER->id);
        file_prepare_draft_area($draftitemid, $context->id, 'phpunit', 'test_get_unused_filename', 1);

        $dummy = array(
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => ''
        );

        // Create some files.
        $existingfiles = array(
            'test',
            'test.txt',
            'test (1).txt',
            'test1.txt',
            'test1 (1).txt',
            'test1 (2).txt',
            'test1 (3).txt',
            'test2 (555).txt',
            'test3 (1000).txt',
        );
        foreach ($existingfiles as $filename) {
            $dummy['filename'] = $filename;
            $file = $fs->create_file_from_string($dummy, 'blah! ' . $filename);
            $this->assertTrue(repository::draftfile_exists($draftitemid, '/', $filename));
        }

        // Create plenty of files.
        for ($i = 1; $i <= 150; $i++) {
            $filename = 'pic (' . $i . ')';
            $dummy['filename'] = $filename . '.jpg';
            $file = $fs->create_file_from_string($dummy, 'foo! ' . $filename);
            $this->assertTrue(repository::draftfile_exists($draftitemid, '/', $dummy['filename']));
            $filename = 'pic (1) (' . $i . ')';
            $dummy['filename'] = $filename . '.jpg';
            $file = $fs->create_file_from_string($dummy, 'bar! ' . $filename);
            $this->assertTrue(repository::draftfile_exists($draftitemid, '/', $dummy['filename']));
        }
        $dummy['filename'] = 'pic (1) (40) (1).jpg';
        $file = $fs->create_file_from_string($dummy, 'lal! ' . $filename);
        $this->assertTrue(repository::draftfile_exists($draftitemid, '/', $dummy['filename']));
        $dummy['filename'] = 'pic (4) (50).jpg';
        $file = $fs->create_file_from_string($dummy, 'lol! ' . $filename);
        $this->assertTrue(repository::draftfile_exists($draftitemid, '/', $dummy['filename']));

        // Actual testing.
        $this->assertEquals('free.txt', repository::get_unused_filename($draftitemid, '/', 'free.txt'));
        $this->assertEquals('test (1)', repository::get_unused_filename($draftitemid, '/', 'test'));
        $this->assertEquals('test (2).txt', repository::get_unused_filename($draftitemid, '/', 'test.txt'));
        $this->assertEquals('test1 (4).txt', repository::get_unused_filename($draftitemid, '/', 'test1.txt'));
        $this->assertEquals('test1 (8).txt', repository::get_unused_filename($draftitemid, '/', 'test1 (8).txt'));
        $this->assertEquals('test1 ().txt', repository::get_unused_filename($draftitemid, '/', 'test1 ().txt'));
        $this->assertEquals('test2 (556).txt', repository::get_unused_filename($draftitemid, '/', 'test2 (555).txt'));
        $this->assertEquals('test3 (1001).txt', repository::get_unused_filename($draftitemid, '/', 'test3 (1000).txt'));
        $this->assertEquals('test4 (1).txt', repository::get_unused_filename($draftitemid, '/', 'test4 (1).txt'));
        $this->assertEquals('pic (1) (1) (1).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (1).jpg'));
        $this->assertEquals('pic (1) (1) (1).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (1) (1).jpg'));
        $this->assertEquals('pic (33) (1).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (33).jpg'));
        $this->assertEquals('pic (1) (33) (1).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (1) (33).jpg'));
        $this->assertEquals('pic (1) (151).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (1) (150).jpg'));
        $this->assertEquals('pic (1) (40) (2).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (1) (40).jpg'));
        $this->assertEquals('pic (4) (51).jpg', repository::get_unused_filename($draftitemid, '/', 'pic (4) (50).jpg'));
    }

}
