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
 * This class represents one tag and also contains lots of useful tag-related methods
 * as static functions.
 *
 * @package    core
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * User class to access user details.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $rawname
 * @property-read int $tagcollid
 * @property-read int $userid
 * @property-read string $tagtype "official" or "default"
 * @property-read string $description
 * @property-read int $descriptionformat
 * @property-read int $flag 0 if not flagged or positive integer if flagged
 * @property-read int $timemodified
 * @property-read moodle_url $viewurl
 * @property-read string $displayname tag name ready to be displayed, with htmlspecialchars() applied
 * @property-read string $cleanname tag name ready to be displayed, without htmlspecialchars()
 *
 * @package    core
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_tag {

    /** @var stdClass data about the tag */
    protected $record = null;

    /**
     * Constructor. Use functions get(), get_by_name(), etc.
     *
     * @param stdClass $record
     */
    protected function __construct($record) {
        if (!isset($record->id)) {
            throw new coding_exeption("Record must contain at least field 'id'");
        }
        $this->record = $record;
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        global $CFG;
        if ($name === 'viewurl') {
            return self::get_view_url($this->record->tagcollid, $this->record->name);
        } else if ($name === 'displayname') {
            require_once($CFG->dirroot . '/tag/lib.php');
            return tag_display_name($this, TAG_RETURN_HTML);
        } else if ($name === 'cleanname') {
            require_once($CFG->dirroot . '/tag/lib.php');
            return tag_display_name($this, TAG_RETURN_TEXT);
        }
        return $this->record->$name;
    }

    /**
     * Magic isset method
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->record->$name);
    }

    /**
     * Converts to object
     *
     * @return stdClass
     */
    public function to_object() {
        return fullclone($this->record);
    }

    /**
     * Adds one or more tag in the database.  This function should not be called directly : you should
     * use tag_set.
     *
     * @param   int      $tagcollid
     * @param   string|array $tags     one tag, or an array of tags, to be created
     * @param   bool     $isofficial type of tag to be created. An official tag is kept even if there are no records tagged with it.
     * @return  array    tag objects indexed by their lowercase normalized names. Any boolean false in the array indicates an error while
     *                             adding the tag.
     */
    protected static function add($tagcollid, $tags, $isofficial = false) {
        global $USER, $DB;

        $tagobject = new stdClass();
        $tagobject->tagtype      = $isofficial ? 'official' : 'default';
        $tagobject->userid       = $USER->id;
        $tagobject->timemodified = time();
        $tagobject->tagcollid    = $tagcollid;

        $rv = array();
        foreach ($tags as $veryrawname) {
            $rawname = clean_param($veryrawname, PARAM_TAG);
            if (!$rawname) {
                $rv[$rawname] = false;
            } else {
                $obj = fullclone($tagobject);
                $obj->rawname = $rawname;
                $obj->name    = core_text::strtolower($rawname);
                $obj->id      = $DB->insert_record('tag', $obj);
                $rv[$obj->name] = new self($obj);

                \core\event\tag_created::create_from_tag($rv[$obj->name])->trigger();
            }
        }

        return $rv;
    }

    /**
     * Simple function to just return a single tag object by its id
     *
     * @param    int    $id
     * @param    string $returnfields which fields do we want returned. This is a comma seperated string containing any combination of
     *                                'id', 'name', 'rawname' or '*' to include all fields.
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means throw exception if no record or multiple records found
     * @return   core_tag|false  tag object
     */
    public static function get($id, $returnfields = 'id, name, rawname, tagcollid', $strictness = IGNORE_MISSING) {
        global $DB;
        $record = $DB->get_record('tag', array('id' => $id), $returnfields, $strictness);
        if ($record) {
            return new self($record);
        }
        return false;
    }

    /**
     * Simple function to just return a single tag object by tagcollid and name
     *
     * @param int $tagcollid tag collection to use,
     *        if 0 is given we will try to guess the tag collection and return the first match
     * @param string $name tag name
     * @param string $returnfields which fields do we want returned. This is a comma separated string
     *         containing any combination of 'id', 'name', 'rawname', 'tagcollid' or '*' to include all fields.
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means throw exception if no record or multiple records found
     * @return core_tag|false tag object
     */
    public static function get_by_name($tagcollid, $name, $returnfields='id, name, rawname, tagcollid',
                        $strictness = IGNORE_MISSING) {
        global $DB;
        if ($tagcollid == 0) {
            $tags = self::guess_by_name($name, $returnfields);
            if ($tags) {
                $tag = reset($tags);
                return $tag;
            } else if ($strictness == MUST_EXIST) {
                throw new dml_missing_record_exception('tag', 'name=?', array($name));
            }
            return false;
        }
        $name = core_text::strtolower($name);   // To cope with input that might just be wrong case.
        $params = array('name' => $name, 'tagcollid' => $tagcollid);
        $record = $DB->get_record('tag', $params, $returnfields, $strictness);
        if ($record) {
            return new self($record);
        }
        return false;
    }

    /**
     * Looking in all tag collections for the tag with the given name
     *
     * @param string $name tag name
     * @param string $returnfields
     * @return array array of core_tag instances
     */
    public static function guess_by_name($name, $returnfields='id, name, rawname, tagcollid') {
        global $DB;
        if (empty($name)) {
            return array();
        }
        $tagcolls = core_tag_collection::get_collections();
        list($sql, $params) = $DB->get_in_or_equal(array_keys($tagcolls), SQL_PARAMS_NAMED);
        $params['name'] = core_text::strtolower($name);
        $tags = $DB->get_records_select('tag', 'name = :name AND tagcollid ' . $sql, $params, '', $returnfields);
        if (count($tags) > 1) {
            // Sort in the same order as tag collections.
            uasort($tags, create_function('$a,$b', '$tagcolls = core_tag_collection::get_collections(); ' .
                'return $tagcolls[$a->tagcollid]->sortorder < $tagcolls[$b->tagcollid]->sortorder ? -1 : 1;'));
        }
        $rv = array();
        foreach ($tags as $id => $tag) {
            $rv[$id] = new self($tag);
        }
        return $rv;
    }

    /**
     * Returns the list of tag objects by tag collection id and the list of tag names
     *
     * @param    int   $tagcollid
     * @param    array $tags array of tags to look for
     * @param    string $returnfields list of DB fields to return, must contain 'id', 'name' and 'rawname'
     * @return   array tag-indexed array of objects. No value for a key means the tag wasn't found.
     */
    public static function get_by_name_bulk($tagcollid, $tags, $returnfields = 'id, name, rawname, tagcollid') {
        global $DB, $CFG;

        if (empty($tags)) {
            return array();
        }

        require_once($CFG->dirroot . '/tag/lib.php');

        $tags = array_values(tag_normalize($tags, TAG_CASE_ORIGINAL));
        $cleantags = tag_normalize($tags); // rawname => normalised name

        list($namesql, $params) = $DB->get_in_or_equal(array_values($cleantags));
        array_unshift($params, $tagcollid);

        $records = $DB->get_records_sql("SELECT $returnfields FROM {tag} WHERE tagcollid = ? AND name $namesql", $params);

        $result = array_fill_keys($cleantags, null);
        foreach ($records as $record) {
            $result[$record->name] = new self($record);
        }
        return $result;
    }

    /**
     * Retrieves tags and/or creates them if do not exist yet
     *
     * @param int $tagcollid
     * @param array $tags array of raw tag names, do not have to be normalised
     * @param bool $createasofficial
     * @return core_tag[] array of tag objects indexed with lowercase normalised tag name
     */
    public static function create_if_missing($tagcollid, $tags, $createasofficial = false) {
        global $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        $tags = array_values(array_filter(tag_normalize($tags, TAG_CASE_ORIGINAL)));
        $cleantags = tag_normalize($tags); // Array rawname => normalised name .

        $result = self::get_by_name_bulk($tagcollid, $tags, '*');
        $existing = array_filter($result);
        $missing = array_diff_key(array_flip($cleantags), $existing); // Array normalised name => rawname.
        if ($missing) {
            $newtags = self::add($tagcollid, array_values($missing), $createasofficial);
            foreach ($newtags as $tag) {
                $result[$tag->name] = $tag;
            }
        }
        return $result;
    }

    /**
     * Returns URL to view the tag
     *
     * @param int $tagcollid
     * @param string $name
     * @return \moodle_url
     */
    public static function get_view_url($tagcollid, $name) {
        return new moodle_url('/tag/index.php',
                    array('tc' => $tagcollid, 'tag' => $name));
    }

    /**
     * Validates that the required fields were retrieved and retrieves them if missing
     *
     * @param array $list array of the fields that need to be validated
     * @param string $caller name of the function that requested it, for the debugging message
     */
    protected function ensure_fields_exist($list, $caller) {
        global $DB;
        $missing = array_diff($list, array_keys((array)$this->record));
        if ($missing) {
            debugging('core_tag::' . $caller . '() must be called on fully retrieved tag object. Missing fields: '.
                    join(', ', $missing), DEBUG_DEVELOPER);
            $this->record = $DB->get_record('tag', array('id' => $this->record->id), '*', MUST_EXIST);
        }
    }

    /**
     * Deletes the tag instance given the record from tag_instance DB table
     *
     * @param stdClass $taginstance
     * @param bool $fullobject whether $taginstance contains all fields from DB table tag_instance
     *          (in this case it is safe to add a record snapshot to the event)
     * @return bool
     */
    protected function delete_instance_as_record($taginstance, $fullobject = false) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        $this->ensure_fields_exist(array('name', 'rawname', 'tagtype'), 'delete_instance_as_record');

        $DB->delete_records('tag_instance', array('id' => $taginstance->id));

        // We can not fire an event with 'null' as the contextid.
        if (is_null($taginstance->contextid)) {
            $taginstance->contextid = context_system::instance()->id;
        }

        // Trigger tag removed event.
        $event = \core\event\tag_removed::create(array(
            'objectid' => $taginstance->id,
            'contextid' => $taginstance->contextid,
            'other' => array(
                'tagid' => $this->id,
                'tagname' => $this->name,
                'tagrawname' => $this->rawname,
                'itemid' => $taginstance->itemid,
                'itemtype' => $taginstance->itemtype
            )
        ));
        if ($fullobject) {
            $event->add_record_snapshot('tag_instance', $taginstance);
        }
        $event->trigger();

        // If there are no other instances of the tag then consider deleting the tag as well.
        if ($this->tagtype === 'default') {
            if (!$DB->record_exists('tag_instance', array('tagid' => $this->id))) {
                tag_delete($this->id);
            }
        }

        return true;
    }

    /**
     * Delete one instance of a tag.  If the last instance was deleted, it will also delete the tag, unless its type is 'official'.
     *
     * @param    string $itemtype the type of the record for which to remove the instance
     * @param    int    $itemid   the id of the record for which to remove the instance
     * @param    int    $tiuserid tag instance user id, only needed for tag areas with user tagging (such as core/course)
     */
    protected function delete_instance($itemtype, $component, $itemid, $tiuserid = 0) {
        global $DB;
        $params = array('tagid' => $this->id,
                'itemtype' => $itemtype, 'itemid' => $itemid);
        if ($tiuserid) {
            $params['tiuserid'] = $tiuserid;
        }
        if ($component) {
            $params['component'] = $component;
        }

        $taginstance = $DB->get_record('tag_instance', $params);
        if (!$taginstance) {
            return;
        }
        $this->delete_instance_as_record($taginstance, true);
    }

    /**
     * Bulk delete all tag instances for a component or tag area
     *
     * @param string $component
     * @param string $itemtype (optional)
     * @param int $contextid (optional)
     */
    public static function delete_instances($component, $itemtype = null, $contextid = null) {
        global $DB;

        $sql = "SELECT ti.*, t.name, t.rawname, t.tagtype
                  FROM {tag_instance} ti
                  JOIN {tag} t
                    ON ti.tagid = t.id
                 WHERE ti.component = :component";
        $params = array('component' => $component);
        if (!is_null($contextid)) {
            $sql .= " AND ti.contextid = :contextid";
            $params['contextid'] = $contextid;
        }
        if (!is_null($itemtype)) {
            $sql .= " AND ti.itemtype = :itemtype";
            $params['itemtype'] = $itemtype;
        }
        if ($taginstances = $DB->get_records_sql($sql, $params)) {
            // Now remove all the tag instances.
            $DB->delete_records('tag_instance', $params);
            // Save the system context in case the 'contextid' column in the 'tag_instance' table is null.
            $syscontextid = context_system::instance()->id;
            // Loop through the tag instances and fire an 'tag_removed' event.
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
                        'tagname' => $taginstance->name,
                        'tagrawname' => $taginstance->rawname,
                        'itemid' => $taginstance->itemid,
                        'itemtype' => $taginstance->itemtype
                    )
                ));
                $event->add_record_snapshot('tag_instance', $taginstance);
                $event->trigger();
            }
        }
    }

    /**
     * Adds a tag instance
     *
     * @param string $itemtype
     * @param string $component
     * @param string $itemid
     * @param context $context
     * @param int $ordering
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging (such as core/course)
     * @return int id of tag_instance
     */
    protected function add_instance($itemtype, $component, $itemid, context $context, $ordering, $tiuserid = 0) {
        global $DB;
        $this->ensure_fields_exist(array('name', 'rawname'), 'add_instance');

        $taginstance = new StdClass;
        $taginstance->tagid        = $this->id;
        $taginstance->component    = $component;
        $taginstance->itemid       = $itemid;
        $taginstance->itemtype     = $itemtype;
        $taginstance->contextid    = $context->id;
        $taginstance->ordering     = $ordering;
        $taginstance->timecreated  = time();
        $taginstance->timemodified = $taginstance->timecreated;
        $taginstance->tiuserid     = $tiuserid;

        $taginstance->id = $DB->insert_record('tag_instance', $taginstance);

        // Trigger tag added event.
        $event = \core\event\tag_added::create(array(
            'objectid' => $taginstance->id,
            'contextid' => $context->id,
            'other' => array(
                'tagid' => $this->id,
                'tagname' => $this->name,
                'tagrawname' => $this->rawname,
                'itemid' => $itemid,
                'itemtype' => $itemtype
            )
        ));
        $event->trigger();

        return $taginstance->id;
    }

    /**
     * Updates the ordering on tag instance
     *
     * @param int $instanceid
     * @param int $ordering
     */
    protected function update_instance($instanceid, $ordering) {
        global $DB;
        $data = new stdClass();
        $data->id = $instanceid;
        $data->ordering     = $ordering;
        $data->timemodified = time();

        $DB->update_record('tag_instance', $data);
    }

    /**
     * Get the array of core_tag objects associated with an item (instances).
     *
     * Use {@link core_tag::get_item_tags_csv()} if you wish to get the same data in a comma-separated string,
     * for instances such as needing to simply display a list of tags to the end user.
     *
     * @param string $itemtype type of the tagged item
     * @param string $component component
     * @param int $itemid
     * @param null|bool $official - true - official only, false - non official only, null - any (default)
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging
     * @return core_tag[] each object contains additional fields taginstanceid, taginstancecontextid and ordering
     */
    public static function get_item_tags($itemtype, $component, $itemid, $official = null, $tiuserid = 0) {
        global $DB;

        if (self::is_enabled($itemtype, $component) === false) {
            // Tagging area is properly defined but not enabled - return empty array.
            return array();
        }

        // Note: if the fields in this query are changed, you need to do the same changes in tag_get_correlated().
        $sql = "SELECT ti.id AS taginstanceid, tg.id, tg.tagtype, tg.name, tg.rawname, tg.flag,
                    tg.tagcollid, ti.ordering, ti.contextid AS taginstancecontextid
                  FROM {tag_instance} ti
                  JOIN {tag} tg ON tg.id = ti.tagid
                  WHERE ti.itemtype = :itemtype AND ti.itemid = :itemid ".
                ($component ? "AND ti.component = :component " : "").
                ($tiuserid ? "AND ti.tiuserid = :tiuserid " : "").
                (($official === true) ? "AND tg.tagtype = :official " : "").
                (($official === false) ? "AND tg.tagtype <> :official " : "").
               "ORDER BY ti.ordering ASC, ti.id";

        $params = array();
        $params['itemtype'] = $itemtype;
        $params['itemid'] = $itemid;
        $params['component'] = $component;
        $params['official'] = 'official';
        $params['tiuserid'] = $tiuserid;

        $records = $DB->get_records_sql($sql, $params);
        $result = array();
        foreach ($records as $id => $record) {
            $result[$id] = new self($record);
        }
        return $result;
    }

    /**
     * Returns the list of display names of the tags that are associated with an item
     *
     * This method is usually used to prefill the form data for the 'tags' form element
     *
     * @param string $itemtype type of the tagged item
     * @param string $component component
     * @param int $itemid
     * @param null|bool $official - true - official only, false - non official only, null - any (default)
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging
     * @return string[] array of tags display names
     */
    public static function get_item_tags_array($itemtype, $component, $itemid, $official = null, $tiuserid = 0) {
        $tags = array();
        foreach (self::get_item_tags($itemtype, $component, $itemid, $official, $tiuserid) as $tag) {
            $tags[$tag->id] = $tag->displayname;
        }
        return $tags;
    }

    /**
     * Returns the comma-separated list of tags that are associated with an item
     *
     * This method can be used to prepare the display of item tags
     *
     * @param string $itemtype type of the tagged item
     * @param string $component component
     * @param int $itemid
     * @param bool $ashtml whether to include the link to the tag page
     * @param null|bool $official - true - official only, false - non official only, null - any (default)
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging
     * @return string comma-separated list of tags
     */
    public static function get_item_tags_csv($itemtype, $component, $itemid, $ashtml = true, $official = null, $tiuserid = 0) {
        $tagsnames = array();
        foreach (self::get_item_tags($itemtype, $component, $itemid, $official, $tiuserid) as $tag) {
            if (!$ashtml) {
                $tagsnames[] = $tag->cleanname;
            } else {
                $tagsnames[] = html_writer::link($tag->viewurl, $tag->displayname);
            }
        }
        return implode(', ', $tagsnames);
    }

    /**
     * Sets the list of tag instances for one item (table record).
     *
     * Extra exsisting instances are removed, new ones are added. New tags
     * are created if needed.
     *
     * This method can not be used for setting tags relations, please use set_related_tags()
     *
     * @param string $itemtype
     * @param string $component
     * @param int $itemid
     * @param context $context
     * @param array $tagnames
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging (such as core/course)
     */
    public static function set_item_tags($itemtype, $component, $itemid, context $context, $tagnames, $tiuserid = 0) {
        if ($itemtype === 'tag') {
            if ($tiuserid) {
                throw new coding_exeption('Related tags can not have tag instance userid');
            }
            debugging('You can not use set_instances() for tagging a tag, please use set_related_tags()', DEBUG_DEVELOPER);
            self::get($itemid, '*', MUST_EXIST)->set_related_tags($tagnames);
            return;
        }

        if ($tagnames !== null && self::is_enabled($itemtype, $component) === false) {
            // Tagging area is properly defined but not enabled - do nothing.
            // Unless we are deleting the item tags ($tagnames === null), in which case proceed with deleting.
            return;
        }

        // Apply clean_param() to all tags.
        if ($tagnames) {
            $tagcollid = core_tag_area::get_collection($itemtype, $component);
            $tagobjects = self::create_if_missing($tagcollid, $tagnames);
        } else {
            $tagobjects = array();
        }

        $currenttags = self::get_item_tags($itemtype, $component, $itemid, null, $tiuserid);

        // For data coherence reasons, it's better to remove deleted tags
        // before adding new data: ordering could be duplicated.
        foreach ($currenttags as $currenttag) {
            if (!array_key_exists($currenttag->name, $tagobjects)) {
                $taginstance = (object)array('id' => $currenttag->taginstanceid,
                    'itemtype' => $itemtype, 'itemid' => $itemid,
                    'contextid' => $currenttag->taginstancecontextid, 'tiuserid' => $tiuserid);
                $currenttag->delete_instance_as_record($taginstance, false);
            }
        }

        $ordering = -1;
        foreach ($tagobjects as $name => $tag) {
            $ordering++;
            foreach ($currenttags as $currenttag) {
                if ($currenttag->name === $name) {
                    if ($currenttag->ordering != $ordering) {
                        $currenttag->update_instance($currenttag->taginstanceid, $ordering);
                    }
                    continue 2;
                }
            }
            $tag->add_instance($itemtype, $component, $itemid, $context, $ordering, $tiuserid);
        }
    }

    /**
     * Removes all tags from an item.
     *
     * All tags will be removed even if tagging is disabled in this area. This is
     * usually called when the item itself has been deleted.
     *
     * @param string $itemtype
     * @param string $component
     * @param int $itemid
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging (such as core/course)
     */
    public static function remove_all_item_tags($itemtype, $component, $itemid, $tiuserid = 0) {
        $context = context_system::instance(); // Context will not be used.
        self::set_item_tags($itemtype, $component, $itemid, $context, null, $tiuserid);
    }

    /**
     * Adds a tag to an item, without overwriting the current tags.
     *
     * If the tag has already been added to the record, no changes are made.
     *
     * @param string $itemtype the type of record to tag ('post' for blogs, 'user' for users, etc.)
     * @param string $component the component that was tagged
     * @param int $itemid the id of the record to tag
     * @param int $context the context of where this tag was assigned
     * @param string $tagname the tag to add
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging (such as core/course)
     * @return int id of tag_instance that was either created or already existed
     */
    public static function add_item_tag($itemtype, $component, $itemid, context $context, $tagname, $tiuserid = 0) {
        global $DB;

        if (self::is_enabled($itemtype, $component) === false) {
            // Tagging area is properly defined but not enabled - do nothing.
            return;
        }

        $rawname = clean_param($tagname, PARAM_TAG);
        $normalisedname = core_text::strtolower($rawname);
        $tagcollid = core_tag_area::get_collection($itemtype, $component);

        $usersql = $tiuserid ? " AND ti.tiuserid = :tiuserid " : "";
        $sql = 'SELECT t.*, ti.id AS taginstanceid
                FROM {tag} t
                LEFT JOIN {tag_instance} ti ON ti.tagid = t.id AND ti.itemtype = :itemtype '.
                $usersql .
                'AND ti.itemid = :itemid AND ti.component = :component
                WHERE t.name = :name AND t.tagcollid = :tagcollid';
        $params = array('name' => $normalisedname, 'tagcollid' => $tagcollid, 'itemtype' => $itemtype,
            'itemid' => $itemid, 'component' => $component, 'tiuserid' => $tiuserid);
        $record = $DB->get_record_sql($sql, $params);
        if ($record) {
            if ($record->taginstanceid) {
                // Tag was already added to the item, nothing to do here.
                return $record->taginstanceid;
            }
            $tag = new self($record);
        } else {
            // The tag does not exist yet, create it.
            $tags = self::add($tagcollid, array($tagname));
            $tag = reset($tags);
        }

        $ordering = $DB->get_field_sql('SELECT MAX(ordering) FROM {tag_instance} ti
                WHERE ti.itemtype = :itemtype AND ti.itemid = itemid AND
                ti.component = :component' . $usersql, $params);

        return $tag->add_instance($itemtype, $component, $itemid, $context, $ordering + 1, $tiuserid);
    }

    /**
     * Removes the tag from an item without changing the other tags
     *
     * @param string $itemtype the type of record to tag ('post' for blogs, 'user' for users, etc.)
     * @param string $component the component that was tagged
     * @param int $itemid the id of the record to tag
     * @param string $tagname the tag to remove
     * @param int $tiuserid tag instance user id, only needed for tag areas with user tagging (such as core/course)
     */
    public static function remove_item_tag($itemtype, $component, $itemid, $tagname, $tiuserid = 0) {
        global $DB;

        if (self::is_enabled($itemtype, $component) === false) {
            // Tagging area is properly defined but not enabled - do nothing.
            return array();
        }

        $rawname = clean_param($tagname, PARAM_TAG);
        $normalisedname = core_text::strtolower($rawname);
        $tagcollid = core_tag_area::get_collection($itemtype, $component);

        $usersql = $tiuserid ? " AND tiuserid = :tiuserid " : "";
        $componentsql = $tiuserid ? " AND ti.component = :component " : "";
        $sql = 'SELECT t.*, ti.id AS taginstanceid, ti.contextid AS taginstancecontextid, ti.ordering
                FROM {tag} t JOIN {tag_instance} ti ON ti.tagid = t.id ' . $usersql . '
                WHERE t.name = :name AND t.tagcollid = :tagcollid AND ti.itemtype = :itemtype
                AND ti.itemid = :itemid ' . $componentsql;
        $params = array('name' => $normalisedname, 'tagcollid' => $tagcollid,
            'itemtype' => $itemtype, 'itemid' => $itemid, 'component' => $component,
            'tiuserid' => $tiuserid);
        if ($record = $DB->get_record_sql($sql, $params)) {
            $taginstance = (object)array('id' => $record->taginstanceid,
                'itemtype' => $itemtype, 'itemid' => $itemid,
                'contextid' => $record->taginstancecontextid, 'tiuserid' => $tiuserid);
            $tag = new self($record);
            $tag->delete_instance_as_record($taginstance, false);
            $sql = "UPDATE {tag_instance} ti SET ordering = ordering - 1
                    WHERE ti.itemtype = :itemtype
                AND ti.itemid = :itemid $componentsql $usersql
                AND ti.ordering > :ordering";
            $params['ordering'] = $record->ordering;
            $DB->execute($sql, $params);
        }
    }

    /**
     * Allows to move all tag instances from one context to another
     *
     * @param string $itemtype the type of record to tag ('post' for blogs, 'user' for users, etc.)
     * @param string $component the component that was tagged
     * @param context $oldcontext
     * @param context $newcontext
     */
    public static function move_context($itemtype, $component, $oldcontext, $newcontext) {
        global $DB;
        if ($oldcontext instanceof context) {
            $oldcontext = $oldcontext->id;
        }
        if ($newcontext instanceof context) {
            $newcontext = $newcontext->id;
        }
        $DB->set_field('tag_instance', 'contextid', $newcontext,
                array('component' => $component, 'itemtype' => $itemtype, 'contextid' => $oldcontext));
    }

    /**
     * Moves all tags of the specified items to the new context
     *
     * @param string $itemtype the type of record to tag ('post' for blogs, 'user' for users, etc.)
     * @param string $component the component that was tagged
     * @param array $itemids
     * @param context $newcontext
     * @return type
     */
    public static function change_items_context($itemtype, $component, $itemids, $newcontext) {
        global $DB;
        if (empty($itemids)) {
            return;
        }
        if (!is_array($itemids)) {
            $itemids = array($itemids);
        }
        list($sql, $params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
        $params['component'] = $component;
        $params['itemtype'] = $itemtype;
        if ($newcontext instanceof context) {
            $newcontext = $newcontext->id;
        }
        $DB->set_field_select('tag_instance', 'contextid', $newcontext,
            'component = :component AND itemtype = :itemtype AND itemid ' . $sql, $params);
    }

    /**
     * Updates the information about the tag
     *
     * @param array|stdClass $data data to update, may contain: tagtype, description, descriptionformat, rawname
     * @return bool whether the tag was updated. False may be returned if: all new values match the existing,
     *         or an invalid tagtype was supplied, or it was attempted to rename the tag to the name that is already used.
     */
    public function update($data) {
        global $DB, $COURSE;

        $allowedfields = array('tagtype', 'description', 'descriptionformat', 'rawname');

        $data = (array)$data;
        if ($extrafields = array_diff(array_keys($data), $allowedfields)) {
            debugging('The field(s) '.join(', ', $extrafields).' will be ignored when updating the tag',
                    DEBUG_DEVELOPER);
        }
        $data = array_intersect_key($data, array_fill_keys($allowedfields, 1));
        $this->ensure_fields_exist(array_merge(array('tagcollid', 'userid', 'name', 'rawname'), array_keys($data)), 'update');

        // Validate the tag name.
        if (array_key_exists('rawname', $data)) {
            $data['rawname'] = clean_param($data['rawname'], PARAM_TAG);
            $name = core_text::strtolower($data['rawname']);

            if (!$name) {
                unset($data['rawname']);
            } else if ($existing = self::get_by_name($this->tagcollid, $name, 'id')) {
                // Prevent the rename if a tag with that name already exists.
                if ($existing->id != $this->id) {
                    debugging('New tag name already exists, you should check it before calling core_tag::update()', DEBUG_DEVELOPER);
                    unset($data['rawname']);
                }
            }
            if (isset($data['rawname'])) {
                $data['name'] = $name;
            }
        }

        // Validate the tag type.
        if (array_key_exists('tagtype', $data) && $data['tagtype'] !== 'official' && $data['tagtype'] !== 'default') {
            debugging('Unrecognised tag type "'.$data['tagtype'].'" ignored when updating the tag', DEBUG_DEVELOPER);
            unset($data['tagtype']);
        }

        // Find only the attributes that need to be changed.
        $originalname = $this->name;
        foreach ($data as $key => $value) {
            if ($this->record->$key !== $value) {
                $this->record->$key = $value;
            } else {
                unset($data[$key]);
            }
        }
        if (empty($data)) {
            return false;
        }

        $data['id'] = $this->id;
        $data['timemodified'] = time();
        $DB->update_record('tag', $data);

        $event = \core\event\tag_updated::create(array(
            'objectid' => $this->id,
            'relateduserid' => $this->userid,
            'context' => context_system::instance(),
            'other' => array(
                'name' => $this->name,
                'rawname' => $this->rawname
            )
        ));
        if (isset($data['rawname'])) {
            $event->set_legacy_logdata(array($COURSE->id, 'tag', 'update', 'index.php?id='. $this->id,
                $originalname . '->'. $this->name));
        }
        $event->trigger();
        return true;
    }

    /**
     * Flag a tag as inappropriate
     */
    public function flag() {
        global $DB;

        $this->ensure_fields_exist(array('name', 'userid', 'rawname', 'flag'), 'flag');

        // Update all the tags to flagged.
        $this->timemodified = time();
        $this->flag++;
        $DB->update_record('tag', array('timemodified' => $this->timemodified,
            'flag' => $this->flag, 'id' => $this->id));

        $event = \core\event\tag_flagged::create(array(
            'objectid' => $this->id,
            'relateduserid' => $this->userid,
            'context' => context_system::instance(),
            'other' => array(
                'name' => $this->name,
                'rawname' => $this->rawname
            )

        ));
        $event->trigger();
    }

    /**
     * Remove the inappropriate flag on a tag.
     */
    function reset_flag() {
        global $DB;

        $this->ensure_fields_exist(array('name', 'userid', 'rawname', 'flag'), 'flag');

        if (!$this->flag) {
            // Nothing to do.
            return false;
        }

        $this->timemodified = time();
        $this->flag = 0;
        $DB->update_record('tag', array('timemodified' => $this->timemodified,
            'flag' => 0, 'id' => $this->id));

        $event = \core\event\tag_unflagged::create(array(
            'objectid' => $this->id,
            'relateduserid' => $this->userid,
            'context' => context_system::instance(),
            'other' => array(
                'name' => $this->name,
                'rawname' => $this->rawname
            )
        ));
        $event->trigger();
    }

    /**
     * Sets the list of tags related to this one.
     *
     * Tag relations are recorded by two instances linking two tags to each other.
     * For tag relations ordering is not used and may be random.
     *
     * @param array $tagnames
     */
    public function set_related_tags($tagnames) {
        $context = context_system::instance();
        $tagobjects = self::create_if_missing($this->tagcollid, $tagnames);

        $currenttags = self::get_item_tags('tag', 'core', $this->id);

        // For data coherence reasons, it's better to remove deleted tags
        // before adding new data: ordering could be duplicated.
        foreach ($currenttags as $currenttag) {
            if (!array_key_exists($currenttag->name, $tagobjects)) {
                $taginstance = (object)array('id' => $currenttag->taginstanceid,
                    'itemtype' => 'tag', 'itemid' => $this->id,
                    'contextid' => $context->id);
                $currenttag->delete_instance_as_record($taginstance, false);
                $this->delete_instance('tag', 'core', $currenttag->id);
            }
        }

        foreach ($tagobjects as $name => $tag) {
            foreach ($currenttags as $currenttag) {
                if ($currenttag->name === $name) {
                    continue 2;
                }
            }
            $this->add_instance('tag', 'core', $tag->id, $context, 0);
            $tag->add_instance('tag', 'core', $this->id, $context, 0);
            $currenttags[] = $tag;
        }
    }

    /**
     * Adds to the list of related tags without removing existing
     *
     * Tag relations are recorded by two instances linking two tags to each other.
     * For tag relations ordering is not used and may be random.
     *
     * @param array $tagnames
     */
    public function add_related_tags($tagnames) {
        global $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        $context = context_system::instance();
        $tagnames = array_values(array_filter(tag_normalize($tagnames, TAG_CASE_ORIGINAL)));
        $tagobjects = self::create_if_missing($this->tagcollid, $tagnames);

        $currenttags = self::get_item_tags('tag', 'core', $this->id);

        foreach ($tagobjects as $name => $tag) {
            foreach ($currenttags as $currenttag) {
                if ($currenttag->name === $name) {
                    continue 2;
                }
            }
            $this->add_instance('tag', 'core', $tag->id, $context, 0);
            $tag->add_instance('tag', 'core', $this->id, $context, 0);
            $currenttags[] = $tag;
        }
    }

    /**
     * Find all items tagged with a tag of a given type ('post', 'user', etc.)
     *
     * @param    string   $itemtype  type to restrict search to
     * @param    string   $component
     * @param    int      $limitfrom (optional, required if $limitnum is set) return a subset of records, starting at this point.
     * @param    int      $limitnum  (optional, required if $limitfrom is set) return a subset comprising this many records.
     * @param    string   $component
     * @return   array of matching objects, indexed by record id, from the table containing the type requested
     */
    public function get_tagged_items($itemtype, $component, $limitfrom = '', $limitnum = '') {
        global $DB;

        if (empty($itemtype) || !$DB->get_manager()->table_exists($itemtype)) {
            return array();
        }

        $query = "SELECT it.*
                    FROM {".$itemtype."} it INNER JOIN {tag_instance} tt ON it.id = tt.itemid
                   WHERE tt.itemtype = ? AND tt.tagid = ?";
        $params = array($itemtype, $this->id);
        if ($component) {
            $query .= ' AND tt.component = ?';
            $params[] = $component;
        }

        return $DB->get_records_sql($query, $params, $limitfrom, $limitnum);
    }

    /**
     * Count how many items are tagged with a specific tag.
     *
     * @param    string   $itemtype  type to restrict search to
     * @param    string   $component
     * @return   int      number of mathing tags.
     */
    public function count_tagged_items($itemtype, $component) {
        global $DB;

        if (empty($itemtype) || !$DB->get_manager()->table_exists($itemtype)) {
            return array();
        }

        $query = "SELECT COUNT(it.id)
                    FROM {".$itemtype."} it INNER JOIN {tag_instance} tt ON it.id = tt.itemid
                   WHERE tt.itemtype = ? AND tt.tagid = ?";
        $params = array($itemtype, $this->id);
        if ($component) {
            $query .= ' AND tt.component = ?';
            $params[] = $component;
        }

        return $DB->get_field_sql($query, $params);
    }

    /**
     * Determine if an item is tagged with a specific tag
     *
     * Note that this is a static method and not a method of core_tag object because the tag might not exist yet,
     * for example user searches for "php" and we offer him to add "php" to his interests.
     *
     * @package core_tag
     * @access  private
     * @param   string   $itemtype    the record type to look for
     * @param   string   $component   component
     * @param   int      $itemid      the record id to look for
     * @param   string   $tagname     a tag name
     * @return  bool/int true if it is tagged, 0 (false) otherwise
     */
    public static function is_item_tagged_with($itemtype, $component, $itemid, $tagname) {
        global $DB;
        $tagcollid = core_tag_area::get_collection($itemtype, $component);
        $query = 'SELECT 1 FROM {tag} t
                    JOIN {tag_instance} ti ON ti.tagid = t.id
                    WHERE t.name = ? AND t.tagcollid = ? AND ti.itemtype = ? AND ti.itemid = ?';
        $cleanname = core_text::strtolower(clean_param($tagname, PARAM_TAG));
        $params = array($cleanname, $tagcollid, $itemtype, $itemid);
        if ($component) {
            $query .= ' AND ti.component = ?';
            $params[] = $component;
        }
        return $DB->record_exists_sql($query, $params) ? 1 : 0;
    }

    /**
     * Returns whether the tag area is enabled
     *
     * @param string $itemtype what is being tagged, for example, 'post', 'course', 'user', etc.
     * @param string $component component responsible for tagging
     * @return bool|null
     */
    public static function is_enabled($itemtype, $component) {
        return core_tag_area::is_enabled($itemtype, $component);
    }
}
