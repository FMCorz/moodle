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
 * Message dialog manager.
 *
 * @module     moodle-core_message-dialog
 * @package    core_message
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var EVENTS = {
    DIALOGCLOSED: 'core_message:dialog-closed'
}

/**
 * Manager.
 *
 * @namespace M.core_message
 * @class Manager
 * @constructor
 */
var MANAGER = function() {
    MANAGER.superclass.constructor.apply(this, arguments);
};
Y.namespace('M.core_message').Manager = Y.extend(MANAGER, Y.Base, {

    _dialogs: {},
    _slots: [],

    initializer: function() {
        this.publishEvents();
        this.setListeners();

        // Load the default dialogs.
        var defaults = this.get('defaultSlots');
        if (defaults) {
            Y.each(defaults, function(data) {
                var dialog;
                if (!data.userid) {
                    return;
                }

                dialog = this.getDialog(data.userid, data.fullname, data.messages);
                this.assignSlot(dialog, false);
            }, this);

            this.notifyPositions();
            Y.each(this._dialogs, function(dialog) {
                dialog.show();
            }, this);
        }
    },

    assignSlot: function(dialog, save) {
        var index = Y.Array.indexOf(this._slots, dialog);
        if (index < 0) {
            this._slots.push(dialog);
        }

        if (typeof save === 'undefined') {
            save = true;
        }

        if (save) {
            this.saveSlots();
        }
    },

    getDialog: function(userid, fullname, messages) {
        if (!this._dialogs[userid]) {

            var dialog = new DIALOG({
                manager: this,
                userid: userid,
                fullname: fullname,
                sendAllowed: this.get('canSend'),
                url: this.get('url'),
                defaultMessages: messages
            });

            this._dialogs[userid] = dialog;
        }
        return this._dialogs[userid];
    },

    getSlotPosition: function(slot) {
        return slot * 280 + 20 + (slot * 10);
    },

    notifyPositions: function() {
        var self = this;
        Y.each(this._slots, function(dialog, i) {
            dialog.positionAdvised(self.getSlotPosition(i), 0);
        }, this);
    },

    publishEvents: function() {
        Y.publish(EVENTS.DIALOGCLOSED, {
            emitFacade: true
        });
    },

    releaseSlot: function(dialog) {
        var index = Y.Array.indexOf(this._slots, dialog),
            reorder;
        if (index < 0) {
            return;
        }
        this._slots.splice(index, 1);
        this.saveSlots();
    },

    saveSlots: function() {
        var slots = [];

        Y.each(this._slots, function(dialog) {
            slots.push({
                userid: dialog.get('userid'),
                status: 'open'
            });
        }, this);

        Y.io(this.get('url'), {
            method: 'POST',
            data: build_querystring({
                sesskey: M.cfg.sesskey,
                action: 'saveslots',
                slots: Y.JSON.stringify(slots)
            })
        });
    },

    setListeners: function() {

        // Listen to clicks on the links opening the dialogs.
        Y.delegate('click', function(e) {
            var target = e.currentTarget,
                fullname = target.getData('core_message-dialog-fullname'),
                dialog = null,
                userid = parseInt(target.getData('core_message-dialog-userid'), 10);

            if (!fullname || !userid) {
                return;
            }

            dialog = this.getDialog(userid, fullname);
            if (!dialog) {
                return;
            }

            e.preventDefault();

            if (dialog.get('visible')) {
                dialog.hide(e)
                this.releaseSlot(dialog);
                this.notifyPositions();
            } else {
                this.assignSlot(dialog);
                dialog.show(e);
                this.notifyPositions();
            }

        }, 'body', '[data-core_message-dialog]', this);

        // Listen to when a dialog is closed.
        Y.on(EVENTS.DIALOGCLOSED, function(e) {
            this.releaseSlot(e.dialog);
            this.notifyPositions();
        }, this);
    }

}, {
    ATTRS: {
        canSend: {
            validator: Y.Lang.isBoolean,
            value: false
        },
        defaultSlots: {
            value: null
        },
        url: {
            validator: Y.Lang.isString,
            valueFn: function() {
                return M.cfg.wwwroot + '/message/ajax.php';
            }
        },
    }
});

Y.namespace('M.core_message.Dialog').init = function(config) {
    return new MANAGER(config);
};
