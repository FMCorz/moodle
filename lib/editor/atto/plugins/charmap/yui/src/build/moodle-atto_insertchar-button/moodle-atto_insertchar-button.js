YUI.add('moodle-atto_charmap-button', function (Y, NAME) {

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
 * Atto text editor charmap plugin.
 *
 * @package    atto_charmap
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.atto_charmap = M.atto_charmap || {
    dialogue: null,
    selection: null,

    init: function(params) {

        var display_chooser = function(e, elementid) {
            e.preventDefault();
            if (!M.editor_atto.is_active(elementid)) {
                M.editor_atto.focus(elementid);
            }
            M.atto_charmap.selection = M.editor_atto.get_selection();
            if (M.atto_charmap.selection === false) {
                return;
            }

            // Initialising the dialogue.
            var dialogue;
            if (!M.atto_charmap.dialogue) {
                dialogue = new M.core.dialogue({
                    visible: false,
                    modal: true,
                    close: true,
                    draggable: true
                });

                // Setting up the content of the dialogue.
                dialogue.set('bodyContent', M.atto_charmap.getDialogueContent(elementid));
                dialogue.set('headerContent', M.util.get_string('charmapacter', 'atto_charmap'));
                dialogue.render();
                dialogue.centerDialogue();
                M.atto_charmap.dialogue = dialogue;
            } else {
                dialogue = M.atto_charmap.dialogue;
            }

            dialogue.show();
        };

        var iconurl = M.util.image_url('e/special_character', 'core');
        M.editor_atto.add_toolbar_button(params.elementid, 'charmap', iconurl, params.group, display_chooser);
    },

    getDialogueContent: function(elementid) {
        var entities = ['copy', 'eacute'],
            character,
            content,
            html = '';

        for (var i in entities) {
            character = entities[i];
            html += '<button class="atto_charmap_character" data-character="&' + Y.Escape.html(character) + ';">' +
                    '&' + Y.Escape.html(character) + ';</button>';
        }

        content = Y.Node.create(html);
        Y.delegate('click', M.atto_charmap.insertChar, content, '.atto_charmap_character', this, elementid);
        return content;
    },

    insertChar: function(e, elementid) {
        var button = e.currentTarget,
            character = button.getData('character');

        e.preventDefault();
        e.stopPropagation();
        M.atto_charmap.dialogue.hide();

        M.editor_atto.set_selection(M.atto_charmap.selection);
        if (document.selection && document.selection.createRange().pasteHTML) {
            document.selection.createRange().pasteHTML(character);
        } else {
            document.execCommand('insertHTML', false, character);
        }

        // Clean the YUI ids from the HTML.
        M.editor_atto.text_updated(elementid);
    }
};


}, '@VERSION@', {"requires": ["node", "escape"]});
