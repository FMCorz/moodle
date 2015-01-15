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
    PREFIX: 'core_message_dialog',
    TITLE: 'dialog-title'
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
    _lastUserId: null,
    _messageTemplate: null,
    _sendLocked: false,

    initializer: function() {
        Y.delegate('click', function(e) {
            var target = e.currentTarget,
                fullname = target.getData('core_message-dialog-fullname'),
                userid = parseInt(target.getData('core_message-dialog-userid'), 10);

            if (!fullname || !userid) {
                return;
            }

            this.set('fullname', fullname);
            this.set('userid', userid);
            this.display();

        }, 'body', '[data-core_message-dialog]', this);

        // Prepare the content area.
        tpl = Y.Handlebars.compile(
            '<div class="wrapper {{#cannotSend}}{{CSS.CANNOTSEND}}{{/cannotSend}}">' +
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
                        '<input type="text" class="message-input">' +
                        '<input type="submit" value="Send" class="message-send">' +
                    '</form>' +
                '</div>' +
            '</div>');
        content = Y.Node.create(
            tpl({
                // COMPONENT: COMPONENT,
                cannotSend: !this.get('canSend'),
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

    addMessage: function(message) {
        var container,
            content;

        if (!this._messageTemplate) {
            this._messageTemplate = Y.Handlebars.compile(
                '<div class="message {{mine}}">' +
                    '<div class="content">' +
                    '{{{content}}}' +
                    '</div>' +
                    '<div class="time">' +
                    '{{time}}' +
                    '</div>' +
                '</div>'
            );
        }

        container = this.getBB().one('.messages');
        content = Y.Node.create(this._messageTemplate({
            mine: parseInt(message.useridfrom, 10) === this.get('userid') ? 'mine' : '',
            content: message.text,
            time: message.time
        }));
        container.append(content);

        return content;
    },

    display: function() {
        // Should we reload the data?
        if (this._lastUserId !== this.get('userid')) {

            // Reset to the defaults.
            this.reset();

            // Launch the loading of the messages.
            this.loadMessages();
        }

        // Visual updates.
        this.getBB().one(SELECTORS.TITLE).setHTML(this.get('fullname'));

        // Record the user that we are looking at.
        this._lastUserId = this.get('userid');

        // Show the dialog.
        this.show();
    },

    displayMessages: function(messages) {
        this.getBB().one('.loading').addClass('hidden');

        Y.each(messages, function(message) {
            this.addMessage(message);
        }, this);

        this.scrollToBottom();
        this.centerDialogue();
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
            Y.log('Error loading the messages');
        };

        this.fetchMessages(success, failure);
    },


    reset: function() {
        this.getBB().one('.loading').removeClass('hidden');
        this.getBB().one('.messages').empty();
    },

    scrollToBottom: function() {
        var messagesarea = this.getBB().one('.messages-area');
        messagesarea.set('scrollTop', messagesarea.get('scrollHeight'));
    },

    sendMessage: function(message) {
        if (!message) {
            // Do not send falsy messages.
            return;
        }

        if (this._sendLocked) {
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
                    this.getBB().one('.message-input').set('value', '');
                    this.scrollToBottom();
                },
                failure: function() {

                },
                complete: function() {
                    this.getBB().one('.message-sending').addClass('hidden');
                }
            },
            context:this
        });
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

    _setEvents: function() {
        if (this.get('canSend')) {
            this.getBB().one('.message-send-form form').on('submit', function(e) {
                var message = this.getBB().one('.message-input').get('value');
                this.sendMessage(message);
                e.preventDefault();
            }, this);
        }
    }

}, {
    NAME: 'core_message_dialog',
    CSS_PREFIX: CSS.PREFIX,
    ATTRS: {
        canSend: {
            validator: Y.Lang.isBoolean,
            value: false
        },
        fullname: {
            validator: Y.Lang.isString,
            value: ''
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
        value: [
            'core_message_dialog'
        ]
    },

    /**
     * Whether to focus on the target that caused the Widget to be shown.
     *
     * @attribute focusOnPreviousTargetAfterHide
     * @default true
     * @type Node
     */
    focusOnPreviousTargetAfterHide: {
        value: true
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
        value: '500px'
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
    * @default true
    */
    modal: {
        value: true
    },

   /**
    * Whether the widget should be draggable or not.
    *
    * @attribute draggable
    * @type Boolean
    * @default true
    */
    draggable: {
        value: true
    }

});

Y.namespace('M.core_message.Dialog').init = function(config) {
    return new DIALOG(config);
};
