<?php
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
 * Data provider.
 *
 * @package    core_blog
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_blog\privacy;
defined('MOODLE_INTERNAL') || die();

use blog_entry;
use context;
use context_helper;
use context_user;
use context_system;
use core_tag_tag;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

require_once($CFG->dirroot . '/blog/locallib.php');

/**
 * Data provider class.
 *
 * @package    core_blog
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\subsystem\provider {

    use \core_privacy\local\legacy_polyfill;

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function _get_metadata(collection $collection) {

        $collection->add_database_table('post', [
            'userid' => 'privacy:metadata:post:userid',
            'subject' => 'privacy:metadata:post:subject',
            'summary' => 'privacy:metadata:post:summary',
            'uniquehash' => 'privacy:metadata:post:uniquehash',
            'publishstate' => 'privacy:metadata:post:publishstate',
            'created' => 'privacy:metadata:post:created',
            'lastmodified' => 'privacy:metadata:post:lastmodified',

            // The following columns are unused:
            // coursemoduleid, courseid, moduleid, groupid, rating, usermodified.
        ], 'privacy:metadata:post');

        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');
        $collection->link_subsystem('core_tag', 'privacy:metadata:core_tag');

        $collection->add_database_table('blog_external', [
            'userid' => 'privacy:metadata:external:userid',
            'name' => 'privacy:metadata:external:name',
            'description' => 'privacy:metadata:external:description',
            'url' => 'privacy:metadata:external:url',
            'filtertags' => 'privacy:metadata:external:filtertags',
            'timemodified' => 'privacy:metadata:external:timemodified',
            'timefetched' => 'privacy:metadata:external:timefetched',
        ], 'privacy:metadata:external');

        // We do not report on blog_association because this is just context-related data.

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function _get_contexts_for_userid($userid) {
        global $DB;
        $contextlist = new \core_privacy\local\request\contextlist();

        // There are at least one blog post.
        if ($DB->record_exists('post', ['userid' => $userid])) {
            $sql = "
                SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :ctxlevel
                   AND ctx.instanceid = :ctxuserid";
            $params = [
                'ctxlevel' => CONTEXT_USER,
                'ctxuserid' => $userid,
            ];
            $contextlist->add_from_sql($sql, $params);

            // Add the associated context of the blog posts.
            $sql = "
                SELECT DISTINCT ctx.id
                  FROM {post} p
                  JOIN {blog_association} ba
                    ON ba.blogid = p.id
                  JOIN {context} ctx
                    ON ctx.id = ba.contextid
                 WHERE p.userid = :userid";
            $params = [
                'userid' => $userid,
            ];
            $contextlist->add_from_sql($sql, $params);
        }

        // If there is at least one external blog, we add the user context. This is done this
        // way because we can't directly add context to a contextlist.
        if ($DB->record_exists('blog_external', ['userid' => $userid])) {
            $sql = "
                SELECT ctx.id
                  FROM {context}
                 WHERE ctx.contextlevel = :ctxlevel
                   AND ctx.instanceid = :ctxuserid";
            $params = [
                'ctxlevel' => CONTEXT_USER,
                'ctxuserid' => $userid,
            ];
            $contextlist->add_from_sql($sql, $params);
        }

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $sysctx = context_system::instance();
        $fs = get_file_storage();
        $userid = $contextlist->get_user()->id;
        $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
        $rootpath = [get_string('blog', 'core_blog')];
        $associations = [];

        foreach ($contextlist as $context) {
            switch ($context->contextlevel) {
                case CONTEXT_USER:

                    // This should never ever happen!
                    if ($context->instanceid != $userid) {
                        debugging('The context and user do not match!', DEBUG_DEVELOPER);
                        continue;
                    }

                    // Export blog entries.
                    $recordset = $DB->get_recordset('post', ['userid' => $userid]);
                    foreach ($recordset as $record) {

                        $subject = format_string($record->subject);
                        $path = array_merge($rootpath, [get_string('blogentries', 'core_blog'), $subject . " ({$record->id})"]);
                        $attachments = [];

                        // Manually export the files as they reside in the system context so we can't use
                        // the write's helper methods. The same happens for attachments.
                        foreach ($fs->get_area_files($sysctx->id, 'blog', 'post', $record->id) as $f) {
                            writer::with_context($context)->export_file($path, $f);
                        }
                        foreach ($fs->get_area_files($sysctx->id, 'blog', 'attachment', $record->id) as $f) {
                            writer::with_context($context)->export_file($path, $f);
                        }

                        // Rewrite the summary files.
                        $summary = writer::with_context($context)->rewrite_pluginfile_urls($path, 'blog', 'post',
                            $record->id, $record->summary);

                        // Fetch associations.
                        $assocs = [];
                        $sql = "SELECT ba.contextid, $ctxfields
                                  FROM {blog_association} ba
                                  JOIN {context} ctx
                                    ON ba.contextid = ctx.id
                                 WHERE ba.blogid = :blogid";
                        $assocset = $DB->get_recordset_sql($sql, ['blogid' => $record->id]);
                        foreach ($assocset as $assocrec) {
                            context_helper::preload_from_record($assocrec);
                            $assocctx = context::instance_by_id($assocrec->contextid);
                            $assocs[] = $assocctx->get_context_name();
                        }
                        $assocset->close();

                        // Export associated tags.
                        \core_tag\privacy\provider::export_item_tags($userid, $context, $path, 'blog', 'post', $record->id);

                        // Add blog entry data.
                        $entry = (object) [
                            'userid' => transform::user($record->userid),
                            'subject' => $subject,
                            'summary' => format_text($summary, $record->summaryformat),
                            'uniquehash' => $record->uniquehash,
                            'publishstate' => static::transform_publishstate($record->publishstate),
                            'created' => transform::datetime($record->created),
                            'lastmodified' => transform::datetime($record->lastmodified),
                            'associations' => $assocs
                        ];

                        writer::with_context($context)->export_data($path, $entry);
                    }
                    $recordset->close();

                    // Export external blogs.
                    $recordset = $DB->get_recordset('blog_external', ['userid' => $userid]);
                    foreach ($recordset as $record) {

                        $path = array_merge($rootpath, [get_string('externalblogs', 'core_blog'),
                            $record->name . " ({$record->id})"]);

                        // Export associated tags.
                        \core_tag\privacy\provider::export_item_tags($userid, $context, $path, 'blog', 'external', $record->id);

                        // Add data.
                        $external = (object) [
                            'userid' => transform::user($record->userid),
                            'name' => $record->name,
                            'description' => $record->description,
                            'url' => $record->url,
                            'filtertags' => $record->filtertags,
                            'modified' => transform::datetime($record->timemodified),
                            'lastfetched' => transform::datetime($record->timefetched),
                        ];

                        writer::with_context($context)->export_data($path, $external);
                    }
                    $recordset->close();
                    break;

                case CONTEXT_COURSE:
                case CONTEXT_MODULE:
                    $associations[] = $context->id;
                    break;
            }
        }

        // Export associations.
        if (!empty($associations)) {
            list($insql, $inparams) = $DB->get_in_or_equal($associations, SQL_PARAMS_NAMED);
            $sql = "
                SELECT ba.contextid, p.subject, $ctxfields
                  FROM {post} p
                  JOIN {blog_association} ba
                    ON ba.blogid = p.id
                  JOIN {context} ctx
                    ON ctx.id = ba.contextid
                 WHERE ba.contextid $insql
                   AND p.userid = :userid
              ORDER BY ba.contextid ASC";
            $params = array_merge($inparams, ['userid' => $userid]);

            $path = [get_string('privacy:path:blogassociations', 'core_blog')];

            $flushassocs = function($context, $assocs) use ($path) {
                writer::with_context($context)->export_data($path, (object) [
                    'associations' => $assocs
                ]);
            };

            $lastcontextid = null;
            $assocs = [];
            $recordset = $DB->get_recordset_sql($sql, $params);
            foreach ($recordset as $record) {
                context_helper::preload_from_record($record);

                if ($lastcontextid && $record->contextid != $lastcontextid) {
                    $flushassocs(context::instance_by_id($lastcontextid), $assocs);
                    $assocs = [];
                }
                $assocs[] = format_string($record->subject);
                $lastcontextid = $record->contextid;
            }

            if ($lastcontextid) {
                $flushassocs(context::instance_by_id($lastcontextid), $assocs);
            }

            $recordset->close();
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function _delete_data_for_all_users_in_context(context $context) {
        global $DB;
        switch ($context->contextlevel) {
            case CONTEXT_USER:
                static::delete_all_user_data($context);
                break;

            case CONTEXT_COURSE:
            case CONTEXT_MODULE:
                // We only delete associations here.
                $DB->delete_records('blog_association', ['contextid' => $context->id]);
                break;
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $associationcontextids = [];

        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                static::delete_all_user_data($context);

            } else if ($context->contextlevel == CONTEXT_COURSE) {
                // Only delete the course associations.
                $associationcontextids[] = $context->id;

            } else if ($context->contextlevel == CONTEXT_MODULE) {
                // Only delete the module associations.
                $associationcontextids[] = $context->id;
            }
        }

        // Delete the associations.
        if (!empty($associationcontextids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($associationcontextids, SQL_PARAMS_NAMED);
            $sql = "SELECT ba.id
                      FROM {blog_association} ba
                      JOIN {post} p
                        ON p.id = ba.blogid
                     WHERE ba.contextid $insql
                       AND p.userid = :userid";
            $params = array_merge($inparams, ['userid' => $userid]);
            $DB->delete_records_select('blog_association', "id IN ($sql)", $params);
        }
    }

    /**
     * Helper method to delete all user data.
     *
     * @param context_user $usercontext The user context.
     * @return void
     */
    protected static function delete_all_user_data(context_user $usercontext) {
        global $DB;
        $userid = $usercontext->instanceid;

        // Delete all blog posts.
        $recordset = $DB->get_recordset_select('post', 'userid = :userid', ['userid' => $userid]);
        foreach ($recordset as $record) {
            $entry = new blog_entry(null, $record);
            $entry->delete();   // Takes care of files and associations.
        }
        $recordset->close();

        // Delete all external blogs, and their associated tags.
        $DB->delete_records('blog_external', ['userid' => $userid]);
        core_tag_tag::delete_instances('core', 'blog_external', $usercontext->id);
    }

    /**
     * Transform a publish state.
     *
     * @param string $publishstate The publish state.
     * @return string
     */
    public static function transform_publishstate($publishstate) {
        switch ($publishstate) {
            case 'draft':
                return get_string('publishtonoone', 'core_blog');
            case 'site':
                return get_string('publishtosite', 'core_blog');
            case 'public':
                return get_string('publishtoworld', 'core_blog');
            default:
        }
        return get_string('privacy:unknown', 'core_blog');
    }
}
