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
 * Progress bar.
 *
 * @package    core
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @module     core/progress_bar
 */
define(['jquery', 'core/str'], function($, Str) {

    /**
     * Progress bar.
     *
     * @alias module:core/progress_bar
     * @class
     */
    function Bar(container) {
        this._container = container;
        this._barNode = container.find('.bar');
        this._statusNode = container.find('h2');
        this._remainingNode = container.find('p');

        // Preload strings.
        Str.get_string('secondsleft', 'moodle');

        this._container.on('progress-update', this._onProgressUpdate.bind(this));
    }

    Bar.prototype._onProgressUpdate = function(e) {
        this.update(e.percentage, e.message, e.remaining);
    };

    Bar.prototype.update = function(percentage, message, remaining) {
        percentage = Math.round(percentage, 2);

        this._barNode.text(percentage + '%');
        this._statusNode.text(message);

        if (percentage >= 100) {
            this._container.addClass('progress-success');
            this._remainingNode.text('');

        } else {
            this._container.removeClass('progress-success');
            if (remaining instanceof 'number') {
                Str.get_string('secondsleft', 'moodle', Math.round(remaining, 2)).then(function(txt) {
                    this._remainingNode.text(txt);
                }.bind(this));
            } else {
                this._remainingNode.text('');
            }
        }

        this._barNode.attr('aria-valuenow', percentage);
        this._barNode.css('width', percentage + '%');
    };

    return Bar;

});
