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
 * Language strings.
 *
 * @package    tool_cbemigrate
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowedcourses'] = 'Courses allowed';
$string['allowedcourses_help'] = 'When at least one course is specified, the migration will only occur on the courses listed here.';
$string['cbemigrate:frameworksmigrate'] = 'Migrate competency frameworks';
$string['coursecompetencymigrations'] = 'Course competency migrations';
$string['coursemodulecompetencymigrations'] = 'Course module competency migrations';
$string['coursesfound'] = 'Courses found';
$string['coursemodulesfound'] = 'Course modules found';
$string['coursestartdate'] = 'Courses start date';
$string['coursestartdate_help'] = 'When enabled, courses with a start date prior to the one specified here will not be migrated.';
$string['disallowedcourses'] = 'Disallowed courses';
$string['disallowedcourses_help'] = 'Any course specified here will not be migrated.';
$string['errors'] = 'Errors';
$string['errorwhilemigratingcoursecompetencywithexception'] = 'Error while migrating the course competency: {$a}';
$string['errorwhilemigratingmodulecompetencywithexception'] = 'Error while migrating the course module competency: {$a}';
$string['excludethese'] = 'Exclude these';
$string['explanation'] = 'Migrating a framework means that its competencies attached to courses and course modules will be transferred to another framework. This tool will refer to the ID number of the competencies to match them across frameworks. The form below also allows you to restrict the migration to a subset of courses.';
$string['findingcoursecompetencies'] = 'Finding course competencies';
$string['findingmodulecompetencies'] = 'Finding module competencies';
$string['frameworks'] = 'Frameworks';
$string['limittothese'] = 'Limit to these';
$string['migrateframeworks'] = 'Migrate competency frameworks';
$string['migratefrom'] = 'Migrate from';
$string['migratefrom_help'] = 'Select the framework to migrate from.';
$string['migrateto'] = 'Migrate from';
$string['migrateto_help'] = 'Select the framework to migrate to. Note that it must be not be hidden.';
$string['migratingcourses'] = 'Migrating courses';
$string['missingmappings'] = 'Missing mappings';
$string['performmigration'] = 'Perform migration';
$string['pluginname'] = 'Competencies migration tool';
$string['results'] = 'Results';
$string['startdatefrom'] = 'Start date from';
$string['unmappedin'] = 'Unmapped in {$a}';
$string['warningcouldnotremovecoursecompetency'] = 'The course competency could not be removed.';
$string['warningcouldnotremovemodulecompetency'] = 'The course module competency could not be removed.';
$string['warningdestinationcoursecompetencyalreadyexists'] = 'The destination course competency already exists.';
$string['warningdestinationmodulecompetencyalreadyexists'] = 'The destination course module competency already exists.';
$string['warnings'] = 'Warnings';
