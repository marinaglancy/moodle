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
 * Moodle tag library
 *
 * This file contains methods related to tag management and handling in moodle,
 * however majority of them have been moved to the class {@link core_tag}.
 * If you are developing a moodle plugin, you will most likely not need any
 * functions from this file and only need ones in {@link core_tag}
 *
 * Tags can be added to any database records.
 * $itemtype (or $record_type) refers to the DB table name
 * $itemid (or $record_id) refers to id field in this DB table
 * $component is the component that is responsible for the tag instance
 *
 * BASIC INSTRUCTIONS :
 *  - to "tag a blog post" (for example):
 *        tag_set('post', $blogpost->id, $arrayoftags, 'core', $context);
 *        or
 *        core_tag::set_item_tags('post', 'core', $blogpost->id, $context, $arrayoftags);
 *
 *  - to "remove all the tags on a blog post":
 *        tag_set('post', $blogpost->id, array(), 'core', $context);
 *        or
 *        core_tag::remove_all_item_tags('post', 'core', $blogpost->id);
 *
 * Tag set will create tags that need to be created.
 *
 * @package    core_tag
 * @category   tag
 * @todo       MDL-31090 turn this into a full-fledged categorization system. This could start by
 *             modifying (removing, probably) the 'tag type' to use another table describing the
 *             relationship between tags (parents, sibling, etc.), which could then be merged with
 *             the 'course categorization' system.
 * @see        http://www.php.net/manual/en/function.urlencode.php
 * @copyright  2007 Luiz Cruz <luiz.laydner@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Used to require that the return value from a function is an array.
 * @see tag_set()
 */
define('TAG_RETURN_ARRAY', 0);
/**
 * Used to require that the return value from a function is an object.
 * @see tag_set()
 */
define('TAG_RETURN_OBJECT', 1);
/**
 * Use to specify that HTML free text is expected to be returned from a function.
 * @see tag_display_name()
 */
define('TAG_RETURN_TEXT', 2);
/**
 * Use to specify that encoded HTML is expected to be returned from a function.
 * @see tag_display_name()
 */
define('TAG_RETURN_HTML', 3);

/**
 * Used to specify that we wish a lowercased string to be returned
 * @see tag_normal()
 */
define('TAG_CASE_LOWER', 0);
/**
 * Used to specify that we do not wish the case of the returned string to change
 * @see tag_normal()
 */
define('TAG_CASE_ORIGINAL', 1);

/**
 * Used to specify that we want all related tags returned, no matter how they are related.
 * @see tag_get_related_tags()
 */
define('TAG_RELATED_ALL', 0);
/**
 * Used to specify that we only want back tags that were manually related.
 * @see tag_get_related_tags()
 */
define('TAG_RELATED_MANUAL', 1);
/**
 * Used to specify that we only want back tags where the relationship was automatically correlated.
 * @see tag_get_related_tags()
 */
define('TAG_RELATED_CORRELATED', 2);

///////////////////////////////////////////////////////
/////////////////// PUBLIC TAG API ////////////////////

/// Functions for settings tags  //////////////////////

/**
 * Set the tags assigned to a record.  This overwrites the current tags.
 *
 * This function is meant to be fed the string coming up from the user interface, which contains all tags assigned to a record.
 *
 * Due to API change $component and $contextid are now required. Instead of
 * calling  this function you can use {@link core_tag::set_item_tags()} or
 * {@link core_tag::set_related_tags()}
 *
 * @package core_tag
 * @category tag
 * @access public
 * @param string $record_type the type of record to tag ('post' for blogs, 'user' for users, 'tag' for tags, etc.)
 * @param int $record_id the id of the record to tag
 * @param array $tags the array of tags to set on the record. If given an empty array, all tags will be removed.
 * @param string|null $component the component that was tagged
 * @param int|null $contextid the context id of where this tag was assigned
 * @return bool|null
 */
function tag_set($record_type, $record_id, $tags, $component = null, $contextid = null) {
    if ($record_type === 'tag') {
        return core_tag::get($record_id, '*', MUST_EXIST)->set_related_tags($tags);
    } else {
        if ($component === null || $contextid === null) {
            debugging('You should specify the component and contextid of the item being tagged in your call to tag_set.',
                DEBUG_DEVELOPER);
        }
        if ($contextid === null) {
            $context = context_system::instance();
        } else {
            $context = context::instance_by_id($contextid);
        }
        return core_tag::set_item_tags($record_type, $component, $record_id, $context, $tags);
    }
}

/**
 * Adds a tag to a record, without overwriting the current tags.
 *
 * This function remains here for backward compatiblity. It is recommended to use
 * {@link core_tag::add_item_tag()} or {@link core_tag::add_related_tags()} instead
 *
 * @package core_tag
 * @category tag
 * @access public
 * @param string $record_type the type of record to tag ('post' for blogs, 'user' for users, etc.)
 * @param int $record_id the id of the record to tag
 * @param string $tag the tag to add
 * @param string|null $component the component that was tagged
 * @param int|null $contextid the context id of where this tag was assigned
 * @return bool|null
 */
function tag_set_add($record_type, $record_id, $tag, $component = null, $contextid = null) {

    $new_tags = array();
    foreach (core_tag::get_item_tags($record_type, $component, $record_id) as $current_tag) {
        $new_tags[] = $current_tag->rawname;
    }
    $new_tags[] = $tag;

    return tag_set($record_type, $record_id, $new_tags, $component, $contextid);
}

/**
 * Removes a tag from a record, without overwriting other current tags.
 *
 * This function remains here for backward compatiblity. It is recommended to use
 * {@link core_tag::remove_item_tag()} instead
 *
 * @package core_tag
 * @category tag
 * @access public
 * @param string $record_type the type of record to tag ('post' for blogs, 'user' for users, etc.)
 * @param int $record_id the id of the record to tag
 * @param string $tag the tag to delete
 * @param string|null $component the component that was tagged
 * @param int|null $contextid the context id of where this tag was assigned
 * @return bool|null
 */
function tag_set_delete($record_type, $record_id, $tag, $component = null, $contextid = null) {

    $new_tags = array();
    $tag = core_text::strtolower(clean_param($tag, PARAM_TAG));
    foreach (core_tag::get_item_tags($record_type, $component, $record_id) as $current_tag ) {
        if ($current_tag->name != $tag) {  // Keep all tags but the one specified
            $new_tags[] = $current_tag->name;
        }
    }

    return tag_set($record_type, $record_id, $new_tags, $component, $contextid);
}


/// Functions for getting information about tags //////

/**
 * Simple function to just return a single tag object when you know the name or something
 *
 * Since Moodle 3.0 this function may only be used to retrieve tag by id since
 * name is not unique in tag table and caller must specify tag collection as well.
 * See also {@link core_tag::get()} and {@link core_tag::get_by_name()}
 *
 * @package  core_tag
 * @category tag
 * @access   public
 * @param    string $field        which field do we use to identify the tag: id, name or rawname
 * @param    string $value        the required value of the aforementioned field
 * @param    string $returnfields which fields do we want returned. This is a comma seperated string containing any combination of
 *                                'id', 'name', 'rawname' or '*' to include all fields.
 * @return   mixed  tag object
 */
function tag_get($field, $value, $returnfields='id, name, rawname, tagcollid') {
    global $DB;
    if ($field === 'id') {
        $tag = core_tag::get((int)$value, $returnfields);
    } else if ($field === 'name') {
        debugging('Function tag_get() when searching by tag name may not return correct tag. Use ".'
                . '"core_tag::get_by_name() instead and specify the tag collection.',
                DEBUG_DEVELOPER);
        $tag = core_tag::get_by_name(0, $value, $returnfields);
    } else {
        $params = array($field => $value);
        return $DB->get_record('tag', $params, $returnfields);
    }
    if ($tag) {
        return $tag->to_object();
    }
    return null;
}

/**
 * Returns tags related to a tag
 *
 * Related tags of a tag come from two sources:
 *   - manually added related tags, which are tag_instance entries for that tag
 *   - correlated tags, which are calculated
 *
 * @package  core_tag
 * @category tag
 * @access   public
 * @param    string   $tagid          is a single **normalized** tag name or the id of a tag
 * @param    int      $type           the function will return either manually (TAG_RELATED_MANUAL) related tags or correlated
 *                                    (TAG_RELATED_CORRELATED) tags. Default is TAG_RELATED_ALL, which returns everything.
 * @param    int      $limitnum       (optional) return a subset comprising this many records, the default is 10
 * @return   array    an array of tag objects
 */
function tag_get_related_tags($tagid, $type=TAG_RELATED_ALL, $limitnum=10) {

    $related_tags = array();

    if ( $type == TAG_RELATED_ALL || $type == TAG_RELATED_MANUAL) {
        //gets the manually added related tags
        $related_tags = core_tag::get_item_tags('tag', 'core', $tagid);
    }

    if ( $type == TAG_RELATED_ALL || $type == TAG_RELATED_CORRELATED ) {
        //gets the correlated tags
        $automatic_related_tags = tag_get_correlated($tagid);
        $related_tags = array_merge($related_tags, $automatic_related_tags);
    }

    // Remove duplicated tags (multiple instances of the same tag).
    $seen = array();
    foreach ($related_tags as $instance => $tag) {
        if (isset($seen[$tag->id])) {
            unset($related_tags[$instance]);
        } else {
            $seen[$tag->id] = 1;
        }
    }

    return array_slice($related_tags, 0 , $limitnum);
}

/**
 * Get a comma-separated list of tags related to another tag.
 *
 * @package  core_tag
 * @category tag
 * @access   public
 * @param    array    $related_tags the array returned by tag_get_related_tags
 * @param    int      $html    either TAG_RETURN_HTML (default) or TAG_RETURN_TEXT : return html links, or just text.
 * @return   string   comma-separated list
 */
function tag_get_related_tags_csv($related_tags, $html=TAG_RETURN_HTML) {
    $tags_names = array();
    foreach($related_tags as $tag) {
        if ( $html == TAG_RETURN_TEXT) {
            $tags_names[] = tag_display_name($tag, TAG_RETURN_TEXT);
        } else {
            // TAG_RETURN_HTML
            $viewurl = core_tag::get_view_url($tag->tagcollid, $tag->name);
            $tags_names[] = html_writer::link($viewurl, tag_display_name($tag));
        }
    }

    return implode(', ', $tags_names);
}

/**
 * Delete one or more tag, and all their instances if there are any left.
 *
 * @package  core_tag
 * @category tag
 * @access   public
 * @param    mixed    $tagids one tagid (int), or one array of tagids to delete
 * @return   bool     true on success, false otherwise
 */
function tag_delete($tagids) {
    global $DB;

    if (!is_array($tagids)) {
        $tagids = array($tagids);
    }
    if (empty($tagids)) {
        return;
    }

    // Use the tagids to create a select statement to be used later.
    list($tagsql, $tagparams) = $DB->get_in_or_equal($tagids);

    // Store the tags and tag instances we are going to delete.
    $tags = $DB->get_records_select('tag', 'id ' . $tagsql, $tagparams);
    $taginstances = $DB->get_records_select('tag_instance', 'tagid ' . $tagsql, $tagparams);

    // Delete all the tag instances.
    $select = 'WHERE tagid ' . $tagsql;
    $sql = "DELETE FROM {tag_instance} $select";
    $DB->execute($sql, $tagparams);

    // Delete all the tag correlations.
    $sql = "DELETE FROM {tag_correlation} $select";
    $DB->execute($sql, $tagparams);

    // Delete all the tags.
    $select = 'WHERE id ' . $tagsql;
    $sql = "DELETE FROM {tag} $select";
    $DB->execute($sql, $tagparams);

    // Fire an event that these items were untagged.
    if ($taginstances) {
        // Save the system context in case the 'contextid' column in the 'tag_instance' table is null.
        $syscontextid = context_system::instance()->id;
        // Loop through the tag instances and fire a 'tag_removed'' event.
        foreach ($taginstances as $taginstance) {
            // We can not fire an event with 'null' as the contextid.
            if (is_null($taginstance->contextid)) {
                $taginstance->contextid = $syscontextid;
            }

            // Trigger tag removed event.
            $event = \core\event\tag_removed::create(array(
                'objectid' => $taginstance->id,
                'contextid' => $taginstance->contextid,
                'other' => array(
                    'tagid' => $taginstance->tagid,
                    'tagname' => $tags[$taginstance->tagid]->name,
                    'tagrawname' => $tags[$taginstance->tagid]->rawname,
                    'itemid' => $taginstance->itemid,
                    'itemtype' => $taginstance->itemtype
                )
            ));
            $event->add_record_snapshot('tag_instance', $taginstance);
            $event->trigger();
        }
    }

    // Fire an event that these tags were deleted.
    if ($tags) {
        $context = context_system::instance();
        foreach ($tags as $tag) {
            // Delete all files associated with this tag
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'tag', 'description', $tag->id);
            foreach ($files as $file) {
                $file->delete();
            }

            // Trigger an event for deleting this tag.
            $event = \core\event\tag_deleted::create(array(
                'objectid' => $tag->id,
                'relateduserid' => $tag->userid,
                'context' => $context,
                'other' => array(
                    'name' => $tag->name,
                    'rawname' => $tag->rawname
                )
            ));
            $event->add_record_snapshot('tag', $tag);
            $event->trigger();
        }
    }

    return true;
}

/**
 * Deletes all the tag instances given a component and an optional contextid.
 *
 * @param string $component
 * @param int $contextid if null, then we delete all tag instances for the $component
 */
function tag_delete_instances($component, $contextid = null) {
    core_tag::delete_instances($component, null, $contextid);
}

/**
 * Function that returns the name that should be displayed for a specific tag
 *
 * @package  core_tag
 * @category tag
 * @access   public
 * @param    stdClass|core_tag   $tagobject a line out of tag table, as returned by the adobd functions
 * @param    int      $html TAG_RETURN_HTML (default) will return htmlspecialchars encoded string, TAG_RETURN_TEXT will not encode.
 * @return   string
 */
function tag_display_name($tagobject, $html=TAG_RETURN_HTML) {
    global $CFG;

    if (!isset($tagobject->name)) {
        return '';
    }

    if (empty($CFG->keeptagnamecase)) {
        //this is the normalized tag name
        $tagname = core_text::strtotitle($tagobject->name);
    } else {
        //original casing of the tag name
        $tagname = $tagobject->rawname;
    }

    // clean up a bit just in case the rules change again
    $tagname = clean_param($tagname, PARAM_TAG);

    if ($html == TAG_RETURN_TEXT) {
        return $tagname;
    } else { // TAG_RETURN_HTML
        return htmlspecialchars($tagname);
    }
}

///////////////////////////////////////////////////////
/////////////////// PRIVATE TAG API ///////////////////

/**
 * Function that returns tags that start with some text, for use by the autocomplete feature
 *
 * @package core_tag
 * @access  private
 * @param   string   $text string that the tag names will be matched against
 * @param   int      $tagcollid tag collection id
 * @return  mixed    an array of objects, or false if no records were found or an error occured.
 */
function tag_autocomplete($text, $tagcollid = 0) {
    global $DB;
    if (!$tagcollid) {
        $tagcollid = core_tag_collection::get_default();
    }
    return $DB->get_records_sql("SELECT tg.id, tg.name, tg.rawname, tg.tagcollid
                               FROM {tag} tg
                              WHERE tg.tagcollid = ? AND tg.name LIKE ?",
        array($tagcollid, core_text::strtolower($text)."%"));
}

/**
 * Clean up the tag tables, making sure all tagged object still exists.
 *
 * This should normally not be necessary, but in case related tags are not deleted when the tagged record is removed, this should be
 * done once in a while, perhaps on an occasional cron run.  On a site with lots of tags, this could become an expensive function to
 * call: don't run at peak time.
 *
 * @package core_tag
 * @access  private
 */
function tag_cleanup() {
    global $DB;

    // Get ids to delete from instances where the tag has been deleted. This should never happen apparently.
    $sql = "SELECT ti.id
              FROM {tag_instance} ti
         LEFT JOIN {tag} t ON t.id = ti.tagid
             WHERE t.id IS null";
    $tagids = $DB->get_records_sql($sql);
    $tagarray = array();
    foreach ($tagids as $tagid) {
        $tagarray[] = $tagid->id;
    }

    // Next get ids from instances that have an owner that has been deleted.
    $sql = "SELECT ti.id
              FROM {tag_instance} ti, {user} u
             WHERE ti.itemid = u.id
               AND ti.itemtype = 'user'
               AND u.deleted = 1";
    $tagids = $DB->get_records_sql($sql);
    foreach ($tagids as $tagid) {
        $tagarray[] = $tagid->id;
    }

    // Get the other itemtypes.
    $sql = "SELECT itemtype
              FROM {tag_instance}
             WHERE itemtype <> 'user'
          GROUP BY itemtype";
    $tagitemtypes = $DB->get_records_sql($sql);
    foreach ($tagitemtypes as $key => $notused) {
        $sql = 'SELECT ti.id
                  FROM {tag_instance} ti
             LEFT JOIN {' . $key . '} it ON it.id = ti.itemid
                 WHERE it.id IS null
                 AND ti.itemtype = \'' . $key . '\'';
        $tagids = $DB->get_records_sql($sql);
        foreach ($tagids as $tagid) {
            $tagarray[] = $tagid->id;
        }
    }

    // Get instances for each of the ids to be deleted.
    if (count($tagarray) > 0) {
        list($sqlin, $params) = $DB->get_in_or_equal($tagarray);
        $sql = "SELECT ti.*, COALESCE(t.name, 'deleted') AS name, COALESCE(t.rawname, 'deleted') AS rawname
                  FROM {tag_instance} ti
             LEFT JOIN {tag} t ON t.id = ti.tagid
                 WHERE ti.id $sqlin";
        $instances = $DB->get_records_sql($sql, $params);
        tag_bulk_delete_instances($instances);
    }

    core_tag_collection::cleanup_unused_tags();
}

/**
 * This function will delete numerous tag instances efficiently.
 * This removes tag instances only. It doesn't check to see if it is the last use of a tag.
 *
 * @param array $instances An array of tag instance objects with the addition of the tagname and tagrawname
 *        (used for recording a delete event).
 */
function tag_bulk_delete_instances($instances) {
    global $DB;

    $instanceids = array();
    foreach ($instances as $instance) {
        $instanceids[] = $instance->id;
    }

    // This is a multi db compatible method of creating the correct sql when using the 'IN' value.
    // $insql is the sql statement, $params are the id numbers.
    list($insql, $params) = $DB->get_in_or_equal($instanceids);
    $sql = 'id ' . $insql;
    $DB->delete_records_select('tag_instance', $sql, $params);

    // Now go through and record each tag individually with the event system.
    foreach ($instances as $instance) {
        // Trigger tag removed event (i.e. The tag instance has been removed).
        $event = \core\event\tag_removed::create(array(
            'objectid' => $instance->id,
            'contextid' => $instance->contextid,
            'other' => array(
                'tagid' => $instance->tagid,
                'tagname' => $instance->name,
                'tagrawname' => $instance->rawname,
                'itemid' => $instance->itemid,
                'itemtype' => $instance->itemtype
            )
        ));
        unset($instance->name);
        unset($instance->rawname);
        $event->add_record_snapshot('tag_instance', $instance);
        $event->trigger();
    }
}

/**
 * Calculates and stores the correlated tags of all tags. The correlations are stored in the 'tag_correlation' table.
 *
 * Two tags are correlated if they appear together a lot. Ex.: Users tagged with "computers" will probably also be tagged with "algorithms".
 *
 * The rationale for the 'tag_correlation' table is performance. It works as a cache for a potentially heavy load query done at the
 * 'tag_instance' table. So, the 'tag_correlation' table stores redundant information derived from the 'tag_instance' table.
 *
 * @package core_tag
 * @access  private
 * @param   int      $mincorrelation Only tags with more than $mincorrelation correlations will be identified.
 */
function tag_compute_correlations($mincorrelation = 2) {
    global $DB;

    // This mighty one line query fetches a row from the database for every
    // individual tag correlation. We then need to process the rows collecting
    // the correlations for each tag id.
    // The fields used by this query are as follows:
    //   tagid         : This is the tag id, there should be at least $mincorrelation
    //                   rows for each tag id.
    //   correlation   : This is the tag id that correlates to the above tagid field.
    //   correlationid : This is the id of the row in the tag_correlation table that
    //                   relates to the tagid field and will be NULL if there are no
    //                   existing correlations
    $sql = 'SELECT pairs.tagid, pairs.correlation, pairs.ocurrences, co.id AS correlationid
              FROM (
                       SELECT ta.tagid, tb.tagid AS correlation, COUNT(*) AS ocurrences
                         FROM {tag_instance} ta
                         JOIN {tag} tga ON ta.tagid = tga.id
                         JOIN {tag_instance} tb ON (ta.itemtype = tb.itemtype AND ta.itemid = tb.itemid AND ta.tagid <> tb.tagid)
                         JOIN {tag} tgb ON tb.tagid = tgb.id AND tgb.tagcollid = tga.tagcollid
                     GROUP BY ta.tagid, tb.tagid
                       HAVING COUNT(*) > :mincorrelation
                   ) pairs
         LEFT JOIN {tag_correlation} co ON co.tagid = pairs.tagid
          ORDER BY pairs.tagid ASC, pairs.ocurrences DESC, pairs.correlation ASC';
    $rs = $DB->get_recordset_sql($sql, array('mincorrelation' => $mincorrelation));

    // Set up an empty tag correlation object
    $tagcorrelation = new stdClass;
    $tagcorrelation->id = null;
    $tagcorrelation->tagid = null;
    $tagcorrelation->correlatedtags = array();

    // We store each correlation id in this array so we can remove any correlations
    // that no longer exist.
    $correlations = array();

    // Iterate each row of the result set and build them into tag correlations.
    // We add all of a tag's correlations to $tagcorrelation->correlatedtags[]
    // then save the $tagcorrelation object
    foreach ($rs as $row) {
        if ($row->tagid != $tagcorrelation->tagid) {
            // The tag id has changed so we have all of the correlations for this tag
            $tagcorrelationid = tag_process_computed_correlation($tagcorrelation);
            if ($tagcorrelationid) {
                $correlations[] = $tagcorrelationid;
            }
            // Now we reset the tag correlation object so we can reuse it and set it
            // up for the current record.
            $tagcorrelation = new stdClass;
            $tagcorrelation->id = $row->correlationid;
            $tagcorrelation->tagid = $row->tagid;
            $tagcorrelation->correlatedtags = array();
        }
        //Save the correlation on the tag correlation object
        $tagcorrelation->correlatedtags[] = $row->correlation;
    }
    // Update the current correlation after the last record.
    $tagcorrelationid = tag_process_computed_correlation($tagcorrelation);
    if ($tagcorrelationid) {
        $correlations[] = $tagcorrelationid;
    }


    // Close the recordset
    $rs->close();

    // Remove any correlations that weren't just identified
    if (empty($correlations)) {
        //there are no tag correlations
        $DB->delete_records('tag_correlation');
    } else {
        list($sql, $params) = $DB->get_in_or_equal($correlations, SQL_PARAMS_NAMED, 'param0000', false);
        $DB->delete_records_select('tag_correlation', 'id '.$sql, $params);
    }
}

/**
 * This function processes a tag correlation and makes changes in the database as required.
 *
 * The tag correlation object needs have both a tagid property and a correlatedtags property that is an array.
 *
 * @package core_tag
 * @access  private
 * @param   stdClass $tagcorrelation
 * @return  int/bool The id of the tag correlation that was just processed or false.
 */
function tag_process_computed_correlation(stdClass $tagcorrelation) {
    global $DB;

    // You must provide a tagid and correlatedtags must be set and be an array
    if (empty($tagcorrelation->tagid) || !isset($tagcorrelation->correlatedtags) || !is_array($tagcorrelation->correlatedtags)) {
        return false;
    }

    $tagcorrelation->correlatedtags = join(',', $tagcorrelation->correlatedtags);
    if (!empty($tagcorrelation->id)) {
        // The tag correlation already exists so update it
        $DB->update_record('tag_correlation', $tagcorrelation);
    } else {
        // This is a new correlation to insert
        $tagcorrelation->id = $DB->insert_record('tag_correlation', $tagcorrelation);
    }
    return $tagcorrelation->id;
}

/**
 * Tasks that should be performed at cron time
 *
 * @package core_tag
 * @access private
 */
function tag_cron() {
    tag_compute_correlations();
    tag_cleanup();
}

/**
 * Search for tags with names that match some text
 *
 * @package core_tag
 * @access  private
 * @param   string        $text      escaped string that the tag names will be matched against
 * @param   bool          $ordered   If true, tags are ordered by their popularity. If false, no ordering.
 * @param   int/string    $limitfrom (optional, required if $limitnum is set) return a subset of records, starting at this point.
 * @param   int/string    $limitnum  (optional, required if $limitfrom is set) return a subset comprising this many records.
 * @param   int           $tagcollid
 * @return  array/boolean an array of objects, or false if no records were found or an error occured.
 */
function tag_find_tags($text, $ordered=true, $limitfrom='', $limitnum='', $tagcollid = null) {
    global $DB;

    $text = core_text::strtolower(clean_param($text, PARAM_TAG));

    list($sql, $params) = $DB->get_in_or_equal($tagcollid ? array($tagcollid) : array_keys(core_tag_collection::get_collections()));
    array_unshift($params, "%{$text}%");

    if ($ordered) {
        $query = "SELECT tg.id, tg.name, tg.rawname, tg.tagcollid, COUNT(ti.id) AS count
                    FROM {tag} tg LEFT JOIN {tag_instance} ti ON tg.id = ti.tagid
                   WHERE tg.name LIKE ? AND tg.tagcollid $sql
                GROUP BY tg.id, tg.name, tg.rawname
                ORDER BY count DESC";
    } else {
        $query = "SELECT tg.id, tg.name, tg.rawname, tg.tagcollid
                    FROM {tag} tg
                   WHERE tg.name LIKE ? AND tg.tagcollid $sql";
    }
    return $DB->get_records_sql($query, $params, $limitfrom , $limitnum);
}

/**
 * Get the name of a tag
 *
 * @package core_tag
 * @access  private
 * @param   mixed    $tagids the id of the tag, or an array of ids
 * @return  mixed    string name of one tag, or id-indexed array of strings
 */
function tag_get_name($tagids) {
    global $DB;

    if (!is_array($tagids)) {
        if ($tag = $DB->get_record('tag', array('id'=>$tagids))) {
            return $tag->name;
        }
        return false;
    }

    $tag_names = array();
    foreach($DB->get_records_list('tag', 'id', $tagids) as $tag) {
        $tag_names[$tag->id] = $tag->name;
    }

    return $tag_names;
}

/**
 * Returns the correlated tags of a tag, retrieved from the tag_correlation table. Make sure cron runs, otherwise the table will be
 * empty and this function won't return anything.
 *
 * Correlated tags are calculated in cron based on existing tag instances.
 *
 * This function will return as many entries as there are existing tag instances,
 * which means that there will be duplicates for each tag.
 *
 * If you need only one record for each correlated tag please call:
 *      tag_get_related_tags($tag_id, TAG_RELATED_CORRELATED);
 *
 * @package core_tag
 * @access  private
 * @param   int      $tag_id   is a single tag id
 * @param   int      $notused  this argument is no longer used
 * @return  array    an array of tag objects or an empty if no correlated tags are found
 */
function tag_get_correlated($tag_id, $notused = null) {
    global $DB;

    $tag_correlation = $DB->get_record('tag_correlation', array('tagid'=>$tag_id));

    if (!$tag_correlation || empty($tag_correlation->correlatedtags)) {
        return array();
    }

    // This is (and has to) return the same fields as the query in core_tag::get_item_tags()
    $sql = "SELECT ti.id AS taginstanceid, tg.id, tg.tagtype, tg.name, tg.rawname, tg.flag,
                tg.tagcollid, ti.ordering, ti.contextid AS taginstancecontextid
              FROM {tag} tg
        INNER JOIN {tag_instance} ti ON tg.id = ti.tagid
             WHERE tg.id IN ({$tag_correlation->correlatedtags})
          ORDER BY ti.ordering ASC, ti.id";
    return $DB->get_records_sql($sql);
}

/**
 * Function that normalizes a list of tag names.
 *
 * @package core_tag
 * @access  private
 * @param   array/string $rawtags array of tags, or a single tag.
 * @param   int          $case    case to use for returned value (default: lower case). Either TAG_CASE_LOWER (default) or TAG_CASE_ORIGINAL
 * @return  array        lowercased normalized tags, indexed by the normalized tag, in the same order as the original array.
 *                       (Eg: 'Banana' => 'banana').
 */
function tag_normalize($rawtags, $case = TAG_CASE_LOWER) {

    // cache normalized tags, to prevent costly repeated calls to clean_param
    static $cleaned_tags_lc = array(); // lower case - use for comparison
    static $cleaned_tags_mc = array(); // mixed case - use for saving to database

    if ( !is_array($rawtags) ) {
        $rawtags = array($rawtags);
    }

    $result = array();
    foreach($rawtags as $rawtag) {
        $rawtag = trim($rawtag);
        if (!$rawtag) {
            continue;
        }
        if ( !array_key_exists($rawtag, $cleaned_tags_lc) ) {
            $cleaned_tags_lc[$rawtag] = core_text::strtolower( clean_param($rawtag, PARAM_TAG) );
            $cleaned_tags_mc[$rawtag] = clean_param($rawtag, PARAM_TAG);
        }
        if ( $case == TAG_CASE_LOWER ) {
            $result[$rawtag] = $cleaned_tags_lc[$rawtag];
        } else { // TAG_CASE_ORIGINAL
            $result[$rawtag] = $cleaned_tags_mc[$rawtag];
        }
    }

    return $result;
}

/**
 * Return a list of page types
 *
 * @package core_tag
 * @access  private
 * @param   string   $pagetype       current page type
 * @param   stdClass $parentcontext  Block's parent context
 * @param   stdClass $currentcontext Current context of block
 */
function tag_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return array(
        'tag-*'=>get_string('page-tag-x', 'tag'),
        'tag-index'=>get_string('page-tag-index', 'tag'),
        'tag-search'=>get_string('page-tag-search', 'tag'),
        'tag-manage'=>get_string('page-tag-manage', 'tag')
    );
}
