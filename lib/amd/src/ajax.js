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
 * Standard Ajax wrapper for Moodle. It calls the central Ajax script,
 * which can call any existing webservice using the current session.
 * In addition, it can batch multiple requests and return multiple responses.
 *
 * @module     core/ajax
 * @class      ajax
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
define(['jquery', 'core/config'], function($, config) {

    /** @type {Number} Delay for registering scheduled requests. */
    var DELAY = 50;
    /** @type {Number} Maximum delay for scheduled requests. */
    var MAXDELAY = DELAY * 3;
    /** @type {String} Key to use to identify a pending JS task. */
    var SCHEDULED_PENDING = 'core/amd:scheduled-pending';

    /** @type {Array} Array of scheduled request objects. */
    var scheduledPool = [];
    /** @type {Number} Scheduled requests timeout reference. */
    var scheduledTimeout = null;
    /** @type {Number} Cumulative delay since of the scheduled requests. */
    var delaySinceSchedule = 0;

    /**
     * Success handler. Called when the ajax call succeeds. Checks each response and
     * resolves or rejects the deferred from that request.
     *
     * @method requestSuccess
     * @private
     * @param {Object[]} responses Array of responses containing error, exception and data attributes.
     */
    var requestSuccess = function(responses) {
        // Call each of the success handlers.
        var requests = this;
        var exception = null;
        var i = 0;
        var request;
        var response;

        for (i = 0; i < requests.length; i++) {
            request = requests[i];

            response = responses[i];
            // We may not have responses for all the requests.
            if (typeof response !== "undefined") {
                if (response.error === false) {
                    // Call the done handler if it was provided.
                    request.deferred.resolve(response.data);
                } else {
                    exception = response.exception;
                    break;
                }
            } else {
                // This is not an expected case.
                exception = new Error('missing response');
                break;
            }
        }
        // Something failed, reject the remaining promises.
        if (exception !== null) {
            for (; i < requests.length; i++) {
                request = requests[i];
                request.deferred.reject(exception);
            }
        }
    };

    /**
     * Fail handler. Called when the ajax call fails. Rejects all deferreds.
     *
     * @method requestFail
     * @private
     * @param {jqXHR} jqXHR The ajax object.
     * @param {string} textStatus The status string.
     */
    var requestFail = function(jqXHR, textStatus) {
        // Reject all the promises.
        var requests = this;

        var i = 0;
        for (i = 0; i < requests.length; i++) {
            var request = requests[i];

            if (typeof request.data.fail != "undefined") {
                request.deferred.reject(textStatus);
            }
        }
    };

    /**
     * Make a request object from the request data.
     *
     * @param {Object} requestData The information provided to the public API (methodname, args, ...).
     * @param {Boolean} loginRequired Whether login is required for this request.
     * @return {Object}
     */
    var makeRequestObject = function(requestData, loginRequired) {
        var deferred = $.Deferred();

        // Allow setting done and fail handlers as arguments.
        // This is just a shortcut for the calling code.
        if (typeof requestData.done !== "undefined") {
            deferred.done(requestData.done);
        }
        if (typeof requestData.fail !== "undefined") {
            deferred.fail(requestData.fail);
        }

        return {
            data: requestData,
            loginRequired: loginRequired,
            deferred: deferred
        };
    };

    /**
     * Make requests.
     *
     * @param {Object[]} requests The list of requests to make.
     * @param {Boolean} async Whether the requests must be done asychronously or not.
     * @param {Boolean} loginRequired Whether login is required.
     */
    var doRequests = function(requests, async, loginRequired) {
        var script = config.wwwroot + '/lib/ajax/service.php?sesskey=' + config.sesskey;
        if (!loginRequired) {
            script = config.wwwroot + '/lib/ajax/service-nologin.php?sesskey=' + config.sesskey;
        }

        // Prepare the settings.
        var settings = {
            type: 'POST',
            data: JSON.stringify(requests.map(function(request, index) {
                return {
                    index: index,
                    methodname: request.data.methodname,
                    args: request.data.args
                };
            })),
            context: requests,
            dataType: 'json',
            processData: false,
            async: async,
            contentType: "application/json"
        };

        // Send off the request.
        if (async) {
            $.ajax(script, settings)
                .then(requestSuccess)
                .fail(requestFail);
        } else {
            settings.success = requestSuccess;
            settings.error = requestFail;
            $.ajax(script, settings);
        }
    };

    /**
     * Perform the scheduled requests.
     */
    var performScheduledRequests = function() {
        var poolData = scheduledPool.slice();

        // Reset the scheduled tasks.
        scheduledPool = [];
        delaySinceSchedule = 0;
        scheduledTimeout = null;

        // Determine whether we can send through nologin.
        var loginRequired = poolData.reduce(function(carry, request) {
            return carry || request.loginRequired;
        }, false);

        // Perform the scheduled requests. They must always be asynchronous.
        doRequests(poolData, true, loginRequired);

        // Mark the task as complete.
        M.util.js_complete(SCHEDULED_PENDING);
    };

    /**
     * Schedule a bunch of requests.
     *
     * This will take care of postponing the requests until deemed appropriate.
     *
     * @param {Object[]} requests List of requests to be made.
     */
    var scheduleRequests = function(requests) {
        $.each(requests, function(index, request) {
            scheduledPool.push(request);
        });

        // If we can delay the scheduled requests a bit more, let's do so. This gives
        // us a chance to bulk a few extra requests.
        if (delaySinceSchedule < MAXDELAY) {

            // When we're scheduling a request we need to mark a JS task as pending.
            if (scheduledTimeout === null) {
                M.util.js_pending(SCHEDULED_PENDING);
            }

            // Schedule the requests to be performed.
            clearTimeout(scheduledTimeout);
            delaySinceSchedule += DELAY;
            scheduledTimeout = setTimeout(performScheduledRequests, DELAY, true);

        }
    };

    return /** @alias module:core/ajax */ {
        // Public variables and functions.
        /**
         * Make a series of ajax requests and return all the responses.
         *
         * @method call
         * @param {Object[]} requestsData Array of requests with each containing methodname and args properties.
         *                   done and fail callbacks can be set for each element in the array, or the
         *                   can be attached to the promises returned by this function.
         * @param {Boolean} async Optional, defaults to true.
         *                  If false - this function will not return until the promises are resolved.
         * @param {Boolean} loginRequired Optional, defaults to true.
         *                  If false - this function will call the faster nologin ajax script - but
         *                  will fail unless all functions have been marked as 'loginRequired' => false
         *                  in services.php
         * @return {Promise[]} Array of promises that will be resolved when the ajax call returns.
         */
        call: function(requestsData, async, loginRequired) {
            if (typeof loginRequired === "undefined") {
                loginRequired = true;
            }
            if (typeof async === "undefined") {
                async = true;
            }

            var localPool = requestsData.map(function(requestData) {
                return makeRequestObject(requestData, loginRequired);
            });

            if (async) {
                scheduleRequests(localPool);
            } else {
                doRequests(localPool, async, loginRequired);
            }

            return localPool.map(function(request) {
                return request.deferred.promise();
            });
        }
    };
});
