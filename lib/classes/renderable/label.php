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
 * Label renderable.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\renderable;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/outputcomponents.php');

/**
 * Label renderable class.
 *
 * This represent a label as defined by Bootstrap. A small text, usually one word
 * that is meant to stand out to attract attention.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class label implements \renderable {

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
     * The text of the label.
     *
     * @var string
     */
    public $text;

    /**
     * The type of label.
     *
     * @var string
     */
    public $type;

    /**
     * Constructor.
     *
     * @param string $text The localised text.
     * @param string $type The type of label.
     */
    public function __construct($text, $type = self::TYPE_DEFAULT) {
        $this->text = $text;
        $this->type = $type;
    }

}
