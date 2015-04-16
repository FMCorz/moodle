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
 * myprofile renderer.
 *
 * @package    core_user
 * @copyright  2015 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_user\output\myprofile;
defined('MOODLE_INTERNAL') || die;
/**
 * Report log renderer's for printing reports.
 *
 * @since      Moodle 2.9
 * @package    core_user
 * @copyright  2015 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render the whole tree.
     *
     * @param tree $tree
     *
     * @return string
     */
    public function render_tree(tree $tree) {
        return $this->render_from_template('core/myprofile_tree', $tree->export_for_template($this));
    }

    /**
     * Render a category.
     *
     * @param category $category
     *
     * @return string
     */
    public function render_category(category $category) {
        return $this->render_from_template('core/myprofile_category', $category->export_for_template($this));
    }

    /**
     * Render a node.
     *
     * @param node $node
     *
     * @return string
     */
    public function render_node(node $node) {
        return $this->render_from_template('core/myprofile_node', $node->export_for_template($this));
    }
}
