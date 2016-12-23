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
 * Fetch and render language strings.
 * Hooks into the old M.str global - but can also fetch missing strings on the fly.
 *
 * @module     core/str
 * @class      str
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
// Disable no-restriced-properties because M.str is expected here:
/* eslint-disable no-restricted-properties */
define(['jquery', 'core/ajax', 'core/localstorage'], function($, ajax, storage) {

    /** @type {Promise[]} An array of promises which resolve to the requested string. */
    var stringsCache = {};

    return /** @alias module:core/str */ {
        // Public variables and functions.
        /**
         * Return a promise object that will be resolved into a string eventually (maybe immediately).
         *
         * @method get_string
         * @param {string} key The language string key
         * @param {string} component The language string component
         * @param {string} param The param for variable expansion in the string.
         * @param {string} lang The users language - if not passed it is deduced.
         * @return {Promise}
         */
         // eslint-disable-next-line camelcase
        get_string: function(key, component, param, lang) {
            var request = this.get_strings([{
                key: key,
                component: component,
                param: param,
                lang: lang
            }]);

            return request.then(function(results) {
                return results[0];
            });
        },

        /**
         * Make a batch request to load a set of strings
         *
         * @method get_strings
         * @param {Object[]} requests Array of { key: key, component: component, param: param, lang: lang };
         *                                      See get_string for more info on these args.
         * @return {Promise}
         */
         // eslint-disable-next-line camelcase
        get_strings: function(requests) {

            var i = 0;
            var missing = [];
            var request;
            var key;

            // Try from local storage. If it's there - put it in M.str and resolve it.
            for (i = 0; i < requests.length; i++) {
                request = requests[i];

                // Ensure that the language is set.
                if (typeof request.lang === "undefined") {
                    request.lang = $('html').attr('lang').replace('-', '_');
                }

                // We already have a promise for this.
                key = stringIdentifier(request);
                if (typeof stringsCache[key] !== 'undefined') {
                    continue;
                }

                // Create a new promise for the string. This means that when we request the same
                // string twice, the second will not generate a new Ajax request and will sit on
                // the promise which will be resolved by the first call.
                var promise = $.Deferred();
                stringsCache[key] = promise;

                // Try to restore the string from local storage.
                if (!hasInM(request)) {
                    var cached = storage.get(key);
                    if (cached) {
                        storeInM(request, cached);
                    }
                }

                // If we have it in M.str, then it's easy. Else it's missing.
                if (hasInM(request)) {
                    promise.resolve(M.util.get_string(request.key, request.component, request.param));
                } else {
                    missing.push(i);
                }
            }

            // We got everything, hurray!
            if (missing.length <= 0) {
                return resolveStrings(requests);
            }

            // Something is missing, prepare the Ajax requests for the missing pieces.
            var ajaxRequests = [];
            for (i = 0; i < missing.length; i++) {
                request = requests[missing[i]];
                ajaxRequests.push({
                    methodname: 'core_get_string',
                    args: {
                        stringid: request.key,
                        component: request.component,
                        lang: request.lang,
                        stringparams: []
                    }
                });
            }

            // Resolve all Ajax requests at once, then resolve thee promises of each string cached.
            var ajaxPromises = ajax.call(ajaxRequests, true, false);
            return $.when.apply(null, ajaxPromises).then(function() {
                for (i = 0; i < arguments.length; i++) {
                    var request = requests[missing[i]],
                        data = arguments[i],
                        key = stringIdentifier(request);

                    storeInM(request, data);
                    storage.set(key, data);
                    stringsCache[key].resolve(data);
                }

                return resolveStrings(requests);
            });
        }
    };

    /**
     * Whether the string is in M.str.
     *
     * @param {Object} string String information.
     * @return {Bool}
     */
    function hasInM(string) {
        return typeof M.str[string.component] !== "undefined" &&
            typeof M.str[string.component][string.key] !== "undefined";
    }

    /**
     * Return the promises for each string requested.
     *
     * @param {Array} requests Request objects.
     * @return {Array}
     */
    function resolveStrings(requests) {
        return $.when.apply($.when, requests.map(function(request) {
            var key = stringIdentifier(request);
            return stringsCache[key];
        })).then(function() {
            // Convert the arguments into a single array.
            return Array.prototype.slice.call(arguments);
        });
    }

    /**
     * Whether the string is in M.str.
     *
     * @param {Object} string String information.
     * @param {String} data The string content.
     */
    function storeInM(string, data) {
        if (typeof M.str[string.component] === "undefined") {
            M.str[string.component] = {};
        }
        M.str[string.component][string.key] = data;
    }

    /**
     * Make a unique identifier for the string.
     *
     * @param {Object} string String information.
     * @return {string}
     */
    function stringIdentifier(string) {
        return 'core_str/' + string.key + '/' + string.component + '/' + string.lang;
    }

});
