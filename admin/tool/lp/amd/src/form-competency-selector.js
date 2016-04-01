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
 * Competency selector module.
 *
 * @module     tool_lp/form-competency-selector
 * @class      form-competency-selector
 * @package    tool_lp
 * @copyright  2016 Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str'], function($, Ajax, str) {

    var noSelectionLabel;

    return /** @alias module:tool_lp/form-competency-selector */ {

        processResults: function(selector, results) {
            var competencies = [],
                potentialparentsonly = $(selector).data('potentialparentsonly'),
                frameworkmaxdepth = $(selector).data('frameworkmaxdepth');

            // Enable selecting empty option when mulitple is set to false.
            if (!$(selector).attr('multiple') && potentialparentsonly) {
                competencies.push({
                    value: 0,
                    label: noSelectionLabel
                });
            }

            $.each(results, function(index, competency) {
                var path = String(competency.path),
                    level = path.split('/').length - 2;
                frameworkmaxdepth = parseInt(frameworkmaxdepth);
                if (!potentialparentsonly || (level < frameworkmaxdepth)) {
                    competencies.push({
                        value: competency.id,
                        label: competency.shortname
                    });
                }
            });
            return competencies;
        },

        transport: function(selector, query, success, failure) {
            var promise,
                competencyframeworkid = $(selector).data('competencyframeworkid');

            promise = Ajax.call([{
                methodname: 'tool_lp_search_competencies',
                args: {
                    searchtext: query,
                    competencyframeworkid: competencyframeworkid
                }
            }]);

            if (typeof noSelectionLabel === 'undefined') {
                str.get_string('competencyframeworkroot', 'tool_lp').then(function(txt) {
                    noSelectionLabel = txt;
                    promise[0].done(success);
                    promise[0].fail(failure);
                }).fail(failure);
            } else {
                promise[0].done(success);
                promise[0].fail(success);
            }

            return promise;
        }

    };

});
