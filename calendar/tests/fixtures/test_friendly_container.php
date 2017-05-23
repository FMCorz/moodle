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
 * Container interface.
 *
 * @package    core_calendar
 * @copyright  2017 Frédéric Massart <fred@fmcorz.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\action_factory;
use core_calendar\local\event\container_interface;
use core_calendar\local\event\data_access\event_vault;
use core_calendar\local\event\factories\event_factory;
use core_calendar\local\event\mappers\event_mapper;
use core_calendar\local\event\strategies\raw_event_retrieval_strategy;

require_once(__DIR__ . '/testable_event_factory.php');

/**
 * Container standard.
 */
class core_calendar_test_friendly_container implements container_interface {

    /**
     * @var \stdClass[] An array of cached courses to use with the event factory.
     */
    protected $coursecache = array();

    /**
     * @var \stdClass[] An array of cached modules to use with the event factory.
     */
    protected $modulecache = array();

    public function __construct() {
        $this->actionfactory = new action_factory();
        $this->eventmapper = new event_mapper(new event_factory($this->coursecache, $this->modulecache));
        $this->eventfactory = new core_calendar_testable_event_factory($this->coursecache, $this->modulecache);
        $this->eventretrievalstrategy = new raw_event_retrieval_strategy();
        $this->eventvault = new event_vault($this->eventfactory, $this->eventretrievalstrategy);
    }

    /**
     * Gets the event factory.
     *
     * @return event_factory
     */
    public function get_event_factory() {
        return $this->eventfactory;
    }

    /**
     * Gets the event mapper.
     *
     * @return event_mapper
     */
    public function get_event_mapper() {
        return $this->eventmapper;
    }

    /**
     * Return an event vault.
     *
     * @return event_vault
     */
    public function get_event_vault() {
        return $this->eventvault;
    }

}
