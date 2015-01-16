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
    CANNOTSEND: 'message-cannot-send',
    FROMME: 'message-from-me',
    ISFLOATING: 'fixed-dialog',
    PREFIX: 'core_message_dialog',
    TITLE: 'dialog-title',
    WRAPPER: 'core_message_dialog-wrapper'
};

var SELECTORS = {
    TITLE: '.dialog-title'
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

    _bb: null,
    _ioFetch: null,
    _lastDate: null,
    _loaded: false,
    _messageTemplate: null,
    _sendLocked: false,

    initializer: function() {

        // Prepare the content area.
        tpl = Y.Handlebars.compile(
            '<div class="{{CSS.WRAPPER}}">' +
                '<div class="messages-area">' +
                    '<div class="loading hidden" style="text-align: center;">' +
                        '<img alt="" role="presentation" src="{{{loadingIcon}}}">' +
                    '</div>' +
                    '<div class="messages clearfix">' +
                    '</div>' +
                    '<div class="message-sending hidden" aria-live="polite" style="text-align: right;">' +
                        '<img alt="" role="presentation" src="{{{smallLoadingIcon}}}"> Sending message...' +
                    '</div>' +
                '</div>' +
                '<div class="message-send-form">' +
                    '<form>' +
                        '<div contenteditable="true" class="message-input"></div>' +
                        '<input type="submit" value="Send" class="message-send">' +
                    '</form>' +
                '</div>' +
            '</div>');
        content = Y.Node.create(
            tpl({
                // COMPONENT: COMPONENT,
                cannotSend: !this.get('sendAllowed'),
                CSS: CSS,
                loadingIcon: M.util.image_url('i/loading', 'moodle'),
                smallLoadingIcon: M.util.image_url('i/loading_small', 'moodle')
            })
        );
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);

        // Prepare the title.
        this.getStdModNode(Y.WidgetStdMod.HEADER)
            .prepend(Y.Node.create('<h1 class="' + CSS.TITLE + '"></h1>'));

        // Use standard dialogue class name. This removes the default styling of the footer.
        this.getBB().one('.moodle-dialogue-wrap').addClass('moodle-dialogue-content');

        // Set the events listeners.
        this._setEvents();
    },

    addDate: function(date) {
        var container = this.getBB().one('.messages');
        container.append(Y.Node.create('<div class="message-date"><span>' + date + '</span></div>'));
    },

    addMessage: function(message) {
        var container,
            content;

        if (!this._messageTemplate) {
            this._messageTemplate = Y.Handlebars.compile(
                '<div class="message {{fromMe}}">' +
                    '<div class="message-content">' +
                    '{{{content}}}' +
                    '</div>' +
                    '<div class="message-time">' +
                    '{{time}}' +
                    '</div>' +
                '</div>'
            );
        }

        container = this.getBB().one('.messages');
        content = Y.Node.create(this._messageTemplate({
            fromMe: parseInt(message.useridfrom, 10) !== this.get('userid') ? CSS.FROMME : '',
            content: message.text,
            time: message.time
        }));
        container.append(content);

        return content;
    },

    displayMessages: function(messages) {
        this.getBB().one('.loading').addClass('hidden');

        Y.each(messages, function(message) {
            if (this._lastDate != message.date) {
                this.addDate(message.date);
                this._lastDate = message.date;
            }
            this.addMessage(message);
        }, this);

        this.scrollToBottom();
    },

    fetchMessages: function(success, failure) {
        if (this._ioFetch && this._ioFetch.isInProgress()) {
            this._ioFetch.abort();
        }

        this._ioFetch = Y.io(this.get('url'), {
            method: 'GET',
            data: build_querystring({
                sesskey: M.cfg.sesskey,
                action: 'getmessages',
                userid: this.get('userid')
            }),
            on: {
                start: function() {
                  this.setLockSend(true);
                },
                success: function(id, response) {
                    var data = null,
                        error = false;

                    try {
                        data = Y.JSON.parse(response.responseText);
                        if (data.error) {
                            error = true;
                        }
                    } catch (e) {
                        error = true;
                    }

                    if (error) {
                        failure.apply(this, [id, response]);
                        return;
                    }

                    success.apply(this, [id, response, data]);
                },
                failure: failure,
                complete: function() {
                    this.setLockSend(false);
                }
            },
            context: this
        });
    },

    hide: function() {
        DIALOG.superclass.hide.apply(this, arguments);
        Y.fire(EVENTS.DIALOGCLOSED, {
            dialog: this
        });
    },

    getBB: function() {
        if (!this._bb) {
            this._bb = this.get('boundingBox');
        }
        return this._bb;
    },

    loadMessages: function() {
        var success,
            failure;

        success = function(id, response, data) {
            this.displayMessages(data);
        };

        failure = function() {
        };

        this.fetchMessages(success, failure);
    },

    positionAdvised: function(right, bottom) {
        this.set('position', [right, bottom]);
        this.updatePosition();
    },

    scrollToBottom: function() {
        var messagesarea = this.getBB().one('.messages-area');
        messagesarea.set('scrollTop', messagesarea.get('scrollHeight'));
    },

    sendMessage: function(message) {
        var pure;
        if (!message) {
            // Do not send falsy messages.
            return;
        }

        // Basic validation.
        if (message.replace(' ', '').replace('&nbsp;', '').trim().length < 1) {
            // The message is too short for our liking.
            return;
        }

        if (this._sendLocked || !this.get('sendAllowed')) {
            // Cannot send.
            return;
        }

        this._ioSend = Y.io(this.get('url'), {
            method: 'POST',
            data: build_querystring({
                sesskey: M.cfg.sesskey,
                action: 'sendmessage',
                userid: this.get('userid'),
                message: message
            }),
            on: {
                start: function() {
                    this.getBB().one('.message-input').setHTML('');
                    this.getBB().one('.message-sending').removeClass('hidden');
                    this.scrollToBottom();
                },
                success: function(id, response) {
                    var data = null,
                        error = false;

                    try {
                        data = Y.JSON.parse(response.responseText);
                        if (data.error || !data.id) {
                            error = true;
                        }
                    } catch (e) {
                        error = true;
                    }

                    if (error) {
                        failure.apply(this, [id, response]);
                        return;
                    }

                    this.addMessage(data);
                    this.scrollToBottom();
                },
                failure: failure,
                complete: function() {
                    this.getBB().one('.message-sending').addClass('hidden');
                }
            },
            context:this
        });

        function failure() {
            var alert = new M.core.alert({
                title: 'Message not sent',
                message: 'An error occured while trying to send the message, please try again later.'
            });
            alert.show();
        }
    },

    setLockSend: function(lock) {
        var btn = this.getBB().one('.message-send'),
            input = this.getBB().one('.message-input');

        if (lock) {
            this._sendLocked = true;
            btn.set('disabled', true);
            input.set('disabled', true);
        } else {
            this._sendLocked = false;
            btn.set('disabled', false);
            input.set('disabled', false);
        }
    },

    show: function() {
        // Should we reload the data?
        if (!this._loaded) {

            // Notify for the loading of the new messages.
            this.getBB().one('.loading').removeClass('hidden');

            // Launch the loading of the messages.
            this.loadMessages();

            // Visual updates.
            this.getBB().one(SELECTORS.TITLE).setHTML(this.get('fullname'));
        }
        this._loaded = true;
        DIALOG.superclass.show.apply(this, arguments);
        this.getBB().set('tabIndex', '0').focus();
    },

    updatePosition: function() {
        if (this.shouldResizeFullscreen() || !this.get('position')) {
            // Do not interfere with the fullscreen positioning.
            this.getBB().removeClass(CSS.ISFLOATING);
            return;
        }
        this.getBB().addClass(CSS.ISFLOATING);
        this.getBB().setStyles({
            'right' : this.get('position')[0],
            'bottom' : this.get('position')[1],
            'left': '',
            'top': ''
        });
    },

    _setEvents: function() {
        if (this.get('sendAllowed')) {
            this.getBB().one('.message-send-form form').on('submit', function(e) {
                var message = this.getBB().one('.message-input').get('value');
                this.sendMessage(message);
                e.preventDefault();
            }, this);

            this.getBB().one('.message-input').on('key', function(e) {
                if (e.shiftKey || e.altKey || e.metaKey || e.ctrlKey) {
                    // Only pure 'Enter' key is used.
                    return;
                }
                this.sendMessage(e.currentTarget.getHTML());
                e.preventDefault();
            }, 'down:enter', this);
        }
    }

}, {
    NAME: 'core_message_dialog',
    CSS_PREFIX: CSS.PREFIX,
    ATTRS: {
        sendAllowed: {
            validator: Y.Lang.isBoolean,
            value: false
        },
        fullname: {
            validator: Y.Lang.isString,
            value: ''
        },
        manager: {
            value: null
        },
        position: {
            value: []
        },
        url: {
            validator: Y.Lang.isString,
            valueFn: function() {
                return M.cfg.wwwroot + '/message/ajax.php';
            }
        },
        userid: {
            value: 0
        }
    }
});

Y.Base.modifyAttrs(Y.namespace('M.core_message.Dialog'), {

    /**
     * List of extra classes.
     *
     * @attribute extraClasses
     * @default ['core_message_dialog']
     * @type Array
     */
    extraClasses: {
        valueFn: function() {
            var classes = ['core_message_dialog'];
            if (!this.get('sendAllowed')) {
                classes.push(CSS.CANNOTSEND);
            }
            return classes;
        }
    },

    /**
     * Whether to focus on the target that caused the Widget to be shown.
     *
     * @attribute focusOnPreviousTargetAfterHide
     * @default false
     * @type Node
     */
    focusOnPreviousTargetAfterHide: {
        value: false
    },

    /**
     *
     * Width.
     *
     * @attribute width
     * @default '500px'
     * @type String|Number
     */
    width: {
        value: '220px'
    },

    /**
     * Boolean indicating whether or not the Widget is visible.
     *
     * @attribute visible
     * @default false
     * @type Boolean
     */
    visible: {
        value: false
    },

   /**
    * Whether the widget should be modal or not.
    *
    * @attribute modal
    * @type Boolean
    * @default false
    */
    modal: {
        value: false
    },

   /**
    * Whether the widget should be draggable or not.
    *
    * @attribute draggable
    * @type Boolean
    * @default false
    */
    draggable: {
        value: false
    },

    /**
     * Whether to display the dialogue centrally on the screen.
     *
     * @attribute center
     * @type Boolean
     * @default false
     */
    center: {
        value : false
    }

});
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
        console.log(this.get('defaultSlots'));
    },

    assignSlot: function(dialog) {
        var index = Y.Array.indexOf(this._slots, dialog);
        if (index < 0) {
            this._slots.push(dialog);
        }
    },

    getDialog: function(userid, fullname) {
        if (!this._dialogs[userid]) {

            var dialog = new DIALOG({
                manager: this,
                userid: userid,
                fullname: fullname,
                sendAllowed: this.get('canSend')
            });

            this._dialogs[userid] = dialog;
        }
        return this._dialogs[userid];
    },

    getSlotPosition: function(slot) {
        return slot * 220 + 20 + (slot * 10);
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
                slots: slots
            })
        });
    },

    setListeners: function() {
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
        }
    }
});

Y.namespace('M.core_message.Dialog').init = function(config) {
    return new MANAGER(config);
};


}, '@VERSION@', {"requires": ["escape", "handlebars", "io-base", "json-parse", "moodle-core-notification-dialogue"]});
