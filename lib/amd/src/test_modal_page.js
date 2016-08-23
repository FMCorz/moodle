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
 * Create a modal.
 *
 * @module     core/test_modal_page
 * @class      test_modal_page
 * @package    core
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.2
 */
define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/templates'], function($, ModalFactory, ModalEvents, Templates) {

    var init = function() {
        var trigger1 = $('#create-from-config');
        ModalFactory.create({
            title: 'test title',
            body: 'test body content',
            footer: 'test footer content',
        }, trigger1);

        var trigger2 = $('#modal-save-cancel');
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: 'Modal save cancel',
            body: 'This modal is a save/cancel modal',
        }, trigger2)
        .done(function(modal) {
            modal.getRoot().on(ModalEvents.save, function(e) {
                e.preventDefault();
                modal.setBody('Save event caught and prevented modal from closing');
            });
        });

        var trigger3 = $('#create-large-modal');
        ModalFactory.create({
            large: true,
            title: 'Large modal',
            body: 'This is the body content for a large modal',
            footer: 'footer content',
        }, trigger3);

        var trigger4 = $('#modal-in-modal');
        ModalFactory.create({
            title: 'First modal',
            body: Templates.render('core/modal_test_3', {}),
            footer: 'Fooooter',
        }, trigger4);
    };

    return {
        init: init,
    };
});
