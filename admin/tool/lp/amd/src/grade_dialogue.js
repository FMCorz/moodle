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
 * Grade dialogue.
 *
 * @package    tool_lp
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery',
        'core/notification',
        'core/templates',
        'tool_lp/dialogue',
        'tool_lp/event_base',
        'core/str'],
        function($, Notification, Templates, Dialogue, EventBase, Str) {

    /**
     * Grade dialogue class.
     */
    var Grade = function() {
        EventBase.prototype.constructor.apply(this, []);
    };
    Grade.prototype = Object.create(EventBase.prototype);

    /** @type {Dialogue} The dialogue. */
    Grade.prototype._popup = null;
    /** @type {Boolean} Can grade. */
    Grade.prototype._canGrade = false;
    /** @type {Boolean} Can suggest. */
    Grade.prototype._canSuggest = false;
    /** @type {Object} Scale values. */
    Grade.prototype._scale = null;

    /**
     * After render hook.
     *
     * @return {Promise}
     * @method _afterRender
     * @protected
     */
    Grade.prototype._afterRender = function() {
    };

    /**
     * Close the dialogue.
     *
     * @method close
     */
    Grade.prototype.close = function() {
        this._popup.close();
        this._popup = null;
    };

    /**
     * Opens the picker.
     *
     * @param {Number} competencyId The competency ID of the competency to work on.
     * @method display
     * @return {Promise}
     */
    Grade.prototype.display = function() {
        return this._render().then(function(html) {
            return Str.get_string('rate', 'tool_lp').then(function(title) {
                this._popup = new Dialogue(
                    title,
                    html,
                    this._afterRender.bind(this)
                );
            }.bind(this));
        }.bind(this)).fail(Notification.exception);
    };

    /**
     * Find a node in the dialogue.
     *
     * @param {String} selector
     * @method _find
     * @protected
     */
    Grade.prototype._find = function(selector) {
        return $(this._popup.getContent()).find(selector);
    };

    /**
     * Render the dialogue.
     *
     * @method _render
     * @protected
     * @return {Promise}
     */
    Grade.prototype._render = function() {
        var context = {
            cangrade: true, //this._canGrade,
            cansuggest: true, //this._canSuggest,
            ratings: [
                { value: 1, name: 'test' },
                { value: 1, name: 'test 2', selected: true },
                { value: 1, name: 'test 3' },
            ]
        };
        return Templates.render('tool_lp/competency_grader', context);
    };

    return /** @alias module:tool_lp/grade_dialogue */ Grade;

});
