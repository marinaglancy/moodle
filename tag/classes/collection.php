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
 * Class to manage tag collections
 *
 * @package   core_tag
 * @copyright 2015 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage tag collections
 *
 * @package   core_tag
 * @copyright 2015 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_tag_collection {

    /**
     * Returns the list of tag collections defined in the system.
     *
     * @return array array of objects where each object has properties: id, name, isdefault, itemtypes, sortorder
     */
    public static function get_collections() {
        global $DB;
        $cache = cache::make('core', 'tags');
        if (($tagcolls = $cache->get('tag_coll')) === false) {
            // Retrieve records from DB and create a default one if it is not present.
            $tagcolls = $DB->get_records('tag_coll', null, 'isdefault DESC, sortorder, id');
            if (empty($tagcolls)) {
                // When this method is called for the first time it automatically creates the default tag collection.
                $DB->insert_record('tag_coll', array('isdefault' => 1, 'sortorder' => 0));
                $tagcolls = $DB->get_records('tag_coll');
            } else {
                // Make sure sortorder is correct.
                $idx = 0;
                foreach ($tagcolls as $id => $tagcoll) {
                    if ($tagcoll->sortorder != $idx) {
                        $DB->update_record('tag_coll', array('sortorder' => $idx, 'id' => $id));
                        $tagcolls[$id]->sortorder = $idx;
                    }
                    $idx++;
                }
            }
            $cache->set('tag_coll', $tagcolls);
        }
        return $tagcolls;
    }

    /**
     * Returns the tag collection object
     *
     * @param int $tagcollid
     * @return stdClass
     */
    public static function get_by_id($tagcollid) {
        $tagcolls = self::get_collections();
        if (array_key_exists($tagcollid, $tagcolls)) {
            return $tagcolls[$tagcollid];
        }
        return null;
    }

    /**
     * Returns the list of existing tag collections as id=>name
     *
     * @param bool $unlockedonly
     * @param bool $searchableonly
     * @return array
     */
    public static function get_collections_menu($unlockedonly = false, $searchableonly = false) {
        // TODO implement $searchableonly.
        $tagcolls = self::get_collections();
        $options = array();
        foreach ($tagcolls as $id => $tagcoll) {
            if (!$unlockedonly || empty($tagcoll->component)) {
                $options[$id] = self::display_name($tagcoll);
            }
        }
        return $options;
    }

    /**
     * Returns id of the default tag collection
     *
     * @return int
     */
    public static function get_default() {
        $collections = self::get_collections();
        $keys = array_keys($collections);
        return $keys[0];
    }

    /**
     * Returns formatted name of the tag collection
     *
     * @param stdClass $record record from DB table tag_coll
     * @return string
     */
    public static function display_name($record) {
        $syscontext = context_system::instance();
        if (!empty($record->component)) {
            $identifier = 'tagcollection_' .
                    clean_param($record->name, PARAM_STRINGID);
            $component = $record->component;
            if ($component === 'core') {
                $component = 'tag';
            }
            return get_string($identifier, $component);
        }
        if (isset($record->name)) {
            return format_string($record->name, true, $syscontext);
        } else if ($record->isdefault) {
            return get_string('defautltagcoll', 'tag');
        } else {
            return $record->id;
        }
    }

    /**
     * Returns the list of names of areas (enabled only) that are in this collection.
     *
     * @param int $tagcollid
     * @return array
     */
    public static function get_areas_names($tagcollid) {
        $allitemtypes = core_tag_area::get_areas($tagcollid, true);
        $itemtypes = array();
        foreach ($allitemtypes as $itemtype => $it) {
            foreach ($it as $component => $v) {
                $itemtypes[] = core_tag_area::display_name($itemtype, $component);
            }
        }
        return $itemtypes;
    }

    /**
     * Creates a new tag collection
     *
     * @param stdClass $data data from form core_tag_collection_form
     * @return int|false id of created tag collection or false if failed
     */
    public static function create($data) {
        global $DB;
        $data = (object)$data;
        $tagcolls = self::get_collections();
        $tagcoll = (object)array(
            'name' => $data->name,
            'isdefault' => 0,
            'itemtypes' => '',//$data->itemtypes,
            'component' => !empty($data->component) ? $data->component : null,
            'sortorder' => count($tagcolls)
        );
        $tagcoll->id = $DB->insert_record('tag_coll', $tagcoll);

        // Reset cache.
        cache::make('core', 'tags')->delete('tag_coll');

        \core\event\tag_coll_created::create_from_record($tagcoll)->trigger();
        return $tagcoll->id;
    }

    /**
     * Updates the tag collection information
     *
     * @param stdClass $tagcoll existing record in DB table tag_coll
     * @param stdClass $data data from form core_tag_collection_form
     * @return bool wether the record was updated
     */
    public static function update($tagcoll, $data) {
        global $DB;
        $defaulttagcollid = self::get_default();
        if ($tagcoll->id == $defaulttagcollid) {
            return false;
        }

        // Only name can be updated with this method.
        $updatedata = array_intersect_key((array)$data, array('name' => 1));
        $updatedata['id'] = $tagcoll->id;
        $DB->update_record('tag_coll', $updatedata);

        // Reset cache.
        cache::make('core', 'tags')->delete('tag_coll');

        \core\event\tag_coll_updated::create_from_record($tagcoll)->trigger();

        return true;
    }

    /**
     * Deletes a custom tag collection
     *
     * @param stdClass $tagcoll existing record in DB table tag_coll
     * @return bool wether the tag collection was deleted
     */
    public static function delete($tagcoll) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/tag/lib.php');

        $defaulttagcollid = self::get_default();
        if ($tagcoll->id == $defaulttagcollid) {
            return false;
        }

        // Move all tags from this tag collection to the default one.
        $allitemtypes = core_tag_area::get_areas($tagcoll->id);
        foreach ($allitemtypes as $it) {
            foreach ($it as $v) {
                core_tag_area::update($v, array('tagcollid' => $defaulttagcollid));
            }
        }

        // Delete tags from this tag_coll.
        tag_delete($DB->get_fieldset_select('tag', 'id', 'tagcollid = ?', array($tagcoll->id)));

        // Delete the tag collection.
        $DB->delete_records('tag_coll', array('id' => $tagcoll->id));

        // Reset cache.
        cache::make('core', 'tags')->delete('tag_coll');

        \core\event\tag_coll_deleted::create_from_record($tagcoll)->trigger();

        return true;
    }

    /**
     * Moves the tag collection in the list one position up or down
     *
     * @param stdClass $tagcoll existing record in DB table tag_coll
     * @param int $direction move direction: +1 or -1
     * @return bool
     */
    public static function change_sortorder($tagcoll, $direction) {
        global $DB;
        if ($direction != -1 && $direction != 1) {
            throw coding_exception('Second argument in tag_coll_change_sortorder() can be only 1 or -1');
        }
        $tagcolls = self::get_collections();
        $keys = array_keys($tagcolls);
        $idx = array_search($tagcoll->id, $keys);
        if ($idx === false || $idx == 0 || $idx + $direction < 1 || $idx + $direction >= count($tagcolls)) {
            return false;
        }
        $otherid = $keys[$idx + $direction];
        $DB->update_record('tag_coll', array('id' => $tagcoll->id, 'sortorder' => $idx + $direction));
        $DB->update_record('tag_coll', array('id' => $otherid, 'sortorder' => $idx));
        // Reset cache.
        cache::make('core', 'tags')->delete('tag_coll');
        return true;
    }

    /**
     * Permanently deletes all non-official tags that no longer have any instances pointing to them
     *
     * @param array $collections optional list of tag collections ids to cleanup
     */
    public static function cleanup_unused_tags($collections = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/tag/lib.php');

        $params = array();
        $sql = "SELECT tg.id FROM {tag} tg LEFT OUTER JOIN {tag_instance} ti ON ti.tagid = tg.id
                WHERE ti.id IS NULL AND tg.tagtype = 'default'";
        if ($collections) {
            list($sqlcoll, $params) = $DB->get_in_or_equal($collections);
            $sql .= " AND tg.tagcollid " . $sqlcoll;
        }
        if ($unusedtags = $DB->get_fieldset_sql($sql, $params)) {
            tag_delete($unusedtags);
        }
    }
}