YUI.add('moodle-core_message-dialog', function (Y, NAME) {

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
 * Message dialog.
 *
 * @module     moodle-core_message-dialog
 * @package    core_message
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var CSS = {
    PREFIX: 'core_message_dialog'
};

/**
 * Dialog.
 *
 * @namespace M.core_message
 * @class Dialog
 * @constructor
 */
var DIALOG = function() {
    DIALOG.superclass.constructor.apply(this, arguments);
};
Y.namespace('M.core_message').Dialog = Y.extend(DIALOG, M.core.dialogue, {



}, {
    NAME: 'core_message_dialog',
    CSS_PREFIX: CSS.PREFIX
});

Y.namespace('M.core_message.Dialog').init = function(config) {
    return new DIALOG(config);
};


}, '@VERSION@', {"requires": ["escape", "handlebars", "io-base", "json-parse", "moodle-core-notification-dialogue"]});
