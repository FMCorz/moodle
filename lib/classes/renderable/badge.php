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
 * Badge renderable.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\renderable;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/outputcomponents.php');

/**
 * Badge renderable class.
 *
 * This represent the badge as defined by Bootstrap. A little number indicating
 * how many things need attention on a specific topic. For example, the number of
 * unread messages, or unread forum posts.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge implements \renderable {

    /** @const Default type. */
    const TYPE_DEFAULT = 'default';

    /** @const Success type. */
    const TYPE_SUCCESS = 'success';

    /** @const Danger type. */
    const TYPE_DANGER = 'danger';

    /** @const Warning type. */
    const TYPE_WARNING = 'warning';

    /** @const Info type. */
    const TYPE_INFO = 'info';

    /**
     * The number to be displayed.
     *
     * @var int
     */
    public $count;

    /**
     * The label of the badge, for accessibility purposes (aria-label).
     *
     * @var string
     */
    public $label;

    /**
     * The type of badge.
     *
     * @var string
     */
    public $type;

    /**
     * Constructor.
     *
     * @param int $count The number to display.
     * @param string $label The localised label.
     * @param string $type The type of badge.
     */
    public function __construct($count, $label, $type = self::TYPE_DEFAULT) {
        $this->count = $count;
        $this->label = $label;
        $this->type = $type;
    }

}
