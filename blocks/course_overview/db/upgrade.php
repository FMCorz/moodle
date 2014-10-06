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
 * This file keeps track of upgrades to the course_overview block.
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @since Moodle 2.8
 * @package course_overview
 * @copyright  2014 Frédéric Massart - FMCorz.net
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the course_overview block
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_course_overview_upgrade($oldversion, $block) {
    global $DB;

    if ($oldversion < 2014100600) {
        // Update the previously added entries so that they belong to the right subpage.
        $blockname = 'course_overview';

        // Get the system sub page, if we do not find it then it is to leave the subpage to null.
        // Private => 1 is a reference to the constant MY_PAGE_PRIVATE.
        if ($systempage = $DB->get_record('my_pages', array('userid' => null, 'private' => 1))) {

            // Check to see if this block is already on the default /my page.
            $criteria = array(
                'blockname' => $blockname,
                'parentcontextid' => context_system::instance()->id,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => null,
            );

            if ($record = $DB->get_record('block_instances', $criteria)) {
                $record->subpagepattern = $systempage->id;
                $DB->update_record('block_instances', $record);
            }
        }

        upgrade_block_savepoint(true, 2014100600, $blockname);
    }

    return true;
}
