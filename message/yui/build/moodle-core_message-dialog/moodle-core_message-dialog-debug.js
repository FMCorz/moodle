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
            '<div class="wrapper">' +
                '<div class="messages-area">' +
                    '<div class="loading hidden" style="text-align: center;">' +
                        '<img alt="" role="presentation" src="{{{loadingIcon}}}">' +
                    '</div>' +
                    '<div class="messages">' +
                    '</div>' +
                '</div>' +
                '<div class="form">' +
                    '<input type="text" id="new-message">' +
                    '<input type="submit" value="Send" id="send-message">' +
                '</div>' +
            '</div>');
        content = Y.Node.create(
            tpl({
                // COMPONENT: COMPONENT,
                // CSS: CSS,
                loadingIcon: M.util.image_url('i/loading', 'moodle')
            })
        );
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);

        // Prepare the title.
        this.getStdModNode(Y.WidgetStdMod.HEADER)
            .prepend(Y.Node.create('<h1 class="' + CSS.TITLE + '"></h1>'));

        // Use standard dialogue class name. This removes the default styling of the footer.
        this.get('boundingBox').one('.moodle-dialogue-wrap').addClass('moodle-dialogue-content');
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
        var container,
            messagearea;

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

        this.getBB().one('.loading').addClass('hidden');
        messagesarea = this.getBB().one('.messages-area');
        container = this.getBB().one('.messages');

        Y.each(messages, function(message, id) {
            var content = Y.Node.create(this._messageTemplate({
                mine: parseInt(message.useridfrom, 10) === this.get('userid') ? 'mine' : '',
                content: message.text,
                time: Y.Date.format(Y.Date.parse(message.timecreated * 1000), {format: '%a, %d %b %y %r'})
            }));
            container.append(content)
        }, this);

        // Scroll to the bottom of the area.
        messagesarea.set('scrollTop', messagesarea.get('scrollHeight'));
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
                        failure.apply(this, [id, response, data]);
                        return;
                    }

                    success.apply(this, [id, response, data]);
                },
                failure: failure
            },
            context:this
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

        failure = function(id, response, data) {
            Y.log('Error loading the messages');
        };

        this.fetchMessages(success, failure);
    },

    reset: function() {
        this.getBB().one('.loading').removeClass('hidden');
        this.getBB().one('.messages').empty();
    }

}, {
    NAME: 'core_message_dialog',
    CSS_PREFIX: CSS.PREFIX,
    ATTRS: {
        fullname: {
            validator: Y.Lang.isString,
            value: ''
        },
        url: {
            validator: Y.Lang.isString,
            valueFn: function() {
                return M.cfg.wwwroot + '/message/ajax.php'
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


}, '@VERSION@', {
    "requires": [
        "datatype-date",
        "escape",
        "handlebars",
        "io-base",
        "json-parse",
        "moodle-core-notification-dialogue"
    ]
});
