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
 *
 * @package    core
 * @subpackage course
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to store, cache, render and manage course category
 *
 * @package    core
 * @subpackage course
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursecat implements renderable, cacheable_object, IteratorAggregate {
    /** @var coursecat stores pseudo category with id=0. Use coursecat::get(0) to retrieve */
    protected static $coursecat0;

    /** @var array list of all fields and their short name and default value for caching */
    protected static $coursecatfields = array(
        'id' => array('id', 0),
        'name' => array('na', ''),
        'idnumber' => array('in', null),
        'description' => array('de', null),
        'descriptionformat' => array('df', 0 /*FORMAT_MOODLE*/),
        'parent' => array('pa', 0),
        'sortorder' => array('so', 0),
        'coursecount' => array('cc', 0),
        'visible' => array('vi', 1),
        'visibleold' => array('vo', 1),
        'timemodified' => null, // not cached
        'depth' => array('dh', 1),
        'path' => array('ph', null),
        'theme' => array('th', null)
    );
    
    protected static $contextfields = array(
        'id'           => array('xi', 0),
        'contextlevel' => null, // not cached, always the same CONTEXT_COURSECAT
        'instanceid'   => null, // not cached, equal to coursecat::id
        'path'         => array('xp', 0),
        'depth'        => array('xd', 0)
    );

    /** @var int */
    protected $id;
    
    /** @var string */
    protected $name = '';
    
    /** @var string */
    protected $idnumber = null;
    
    /** @var string */
    protected $description = null;
    
    /** @var int */
    protected $descriptionformat = 0;
    
    /** @var int */
    protected $parent = 0;
    
    /** @var int */
    protected $sortorder = 0;
    
    /** @var int */
    protected $coursecount = 0;
    
    /** @var int */
    protected $visible = 1;
    
    /** @var int */
    protected $visibleold = 1;
    
    /** @var int */
    protected $timemodified = 0;
    
    /** @var int */
    protected $depth = 0;
    
    /** @var string */
    protected $path = '';
    
    /** @var string */
    protected $theme = null;
    
    /** @var bool */
    protected $fromcache;

    // ====== magic methods =======

    /**
     * Magic setter method, we do not want anybody to modify properties from the outside
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        debugging('Can not change coursecat instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Magic method getter, redirects to read only values.
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (array_key_exists($name, self::$coursecatfields)) {
            return $this->$name;
        }
        debugging('Invalid coursecat property accessed! '.$name, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Full support for isset on our magic read only properties.
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        if (array_key_exists($name, self::$coursecatfields)) {
            return isset($this->$name);
        }
        return false;
    }

    /**
     * ALl properties are read only, sorry.
     * @param string $name
     */
    public function __unset($name) {
        debugging('Can not unset coursecat instance properties!', DEBUG_DEVELOPER);
    }

    // ====== implementing method from interface IteratorAggregate ======

    /**
     * Create an iterator because magic vars can't be seen by 'foreach'.
     */
    public function getIterator() {
        $ret = array();
        foreach (self::$coursecatfields as $property => $unused) {
            $ret[$property] = $this->$property;
        }
        return new ArrayIterator($ret);
    }

    // ====== general coursecat methods ======

    /**
     * Constructor
     *
     * Constructor is protected, use coursecat::get($id) to retrieve category
     * 
     * @param stdClass $record
     */
    protected function __construct(stdClass $record, $fromcache = false) {
        context_instance_preload($record);
        foreach ($record as $key => $val) {
            if (array_key_exists($key, self::$coursecatfields)) {
                $this->$key = $val;
            }
        }
        $this->fromcache = $fromcache;
    }

    /**
     * Returns coursecat object for requested category
     *
     * If category is not visible to user it is treated as non existing
     *
     * If id is 0, the pseudo object for root category is returned (convenient
     * for calling other functions such as get_children())
     *
     * @param int $id category id
     * @param int $strictness whether to throw an exception (MUST_EXIST) or
     *     return null (IGNORE_MISSING) in case the category is not found or
     *     not visible to current user
     * @param bool $forcegetall set to true if you know that code will require
     *     building categories tree anyway to avoid extra DB query. Retrieved
     *     categories are cached
     * @return null|\coursecat
     */
    public static function get($id, $strictness = MUST_EXIST, $forcegetall = false) {
        global $DB;
        if (!$id) {
            if (!isset(self::$coursecat0)) {
                $record = array(
                    'id' => 0,
                    'visible' => 1,
                    'depth' => 0
                );
                self::$coursecat0 = new coursecat((object)$record);
            }
            return self::$coursecat0;
        }
        if ($forcegetall) {
            $all = self::get_all_visible();
        } else {
            $coursecatcache = cache::make('core', 'coursecat');
            $all = $coursecatcache->get('all');
            if ($all !== false && $all instanceof coursecat_list) {
                $all = $all->toArray();
            }
        }
        if ($all !== false) {
            if (isset($all[$id])) {
                return $all[$id];
            } else {
                if ($strictness == MUST_EXIST) {
                    // TODO throw properly
                    throw new Exception('not found');
                }
            }
            return null;
        }
                    // TODO check syntax
        list($ccselect, $ccjoin) = context_instance_preload_sql('cc.id', CONTEXT_COURSECAT, 'ctx');
        $sql = "SELECT cc.* $ccselect
                FROM {course_categories} cc
                $ccjoin
                WHERE ID = ?";
        $record = $DB->get_record_sql($sql, $strictness);
        if ($record && ($coursecat = new coursecat($record)) &&
                ($coursecat->is_uservisible())) {
            return $coursecat;
        }
        if ($strictness == MUST_EXIST) {
            // TODO throw properly
            throw new Exception('not found');
        }
        return null;
    }

    /**
     * Checks if this course category is visible to current user
     *
     * This is protected function because non visible categories are not
     * returned to outside world from this class anyway
     */
    protected function is_uservisible() {
        return !$this->id || $this->visible ||
                has_capability('moodle/category:viewhiddencategories',
                        context_coursecat::instance($this->id));
    }

    /**
     * Retrieves and caches all categories visible to the current user
     *
     * This is a generic function that returns an array of
     * (category id => coursecat object) sorted by
     * depth, parent, sortorder, id
     *
     * @return array of coursecat objects
     */
    public static function get_all_visible() {
        global $DB;
        $coursecatcache = cache::make('core', 'coursecat');
        $rv = $coursecatcache->get('all');
        self::mylog("rv = ".print_r($rv,true));
        if ($rv === false) {
            list($ccselect, $ccjoin) = context_instance_preload_sql('cc.id', CONTEXT_COURSECAT, 'ctx');
            $sql = "SELECT cc.* $ccselect
                    FROM {course_categories} cc
                    $ccjoin
                    ORDER BY cc.depth, cc.parent, cc.sortorder, cc.id";
            $rs = $DB->get_recordset_sql($sql, array());
            $rv = array();
            $cntall = 0;
            foreach($rs as $record) {
                $cntall++;
                if (!$record->parent || isset($rv[$record->parent])) {
                    if (($coursecat = new coursecat($record)) && $coursecat->is_uservisible()) {
                        $rv[$record->id] = $coursecat;
                    }
                }
            }
            $rs->close();
            self::mylog("!setting cache");
            $coursecatcache->set('all', new coursecat_list($rv));
            $coursecatcache->set('cntall', $cntall);
        } else if ($rv instanceof coursecat_list) {
            $rv = $rv->toArray();
        }
        return $rv;
    }

    /**
     * Returns number of ALL categories in the system regardless if
     * they are visible to current user or not
     *
     * @return int
     */
    public static function cnt_all() {
        global $DB;
        $coursecatcache = cache::make('core', 'coursecat');
        $cntall = $coursecatcache->get('cntall');
        if ($cntall !== false) {
            return $cntall;
        }
        $cntall = $DB->count_records('course_categories');
        $coursecatcache->set('cntall', $cntall);
        return $cntall;
    }

    /**
     * Returns array of children categories
     * 
     * @return array of coursecat objects indexed by category id
     */
    public function get_children() {
        $all = self::get_all_visible();
        $children = array();
        foreach ($all as $id => $record) {
            if ($record->parent == $this->id) {
                $children[$record->id] = $record;
            }
        }
        return $children;
    }
    
    /**
     * Returns name of the category formatted as a string
     * 
     * @param array $options formatting options other than context
     * @return string
     */
    public function get_formatted_name($options = array()) {
        if ($this->id) {
            $context = context_coursecat::instance($this->id);
            return format_string($this->name, true, array('context' => $context) + $options);
        } else {
            return ''; // TODO 'Top'?
        }
    }

    /**
     * Returns all parents of the element
     *
     * For example, if you have a tree of categories like:
     *   Miscellaneous (id = 1)
     *      Subcategory (id = 2)
     *         Sub-subcategory (id = 4)
     *   Other category (id = 3)
     *
     * coursecat::get(1)->get_all_parents() == array()
     * coursecat::get(2)->get_all_parents() == array(1 => coursecat(...))
     * coursecat::get(4)->get_all_parents() == array(1 => coursecat(...), 2 => coursecat(...));
     *
     * @return array of coursecat objects indexed by category id
     */
    public function get_all_parents() {
        $parents = array();
        if ($this->parent && ($parent = self::get($this->parent, IGNORE_MISSING, true))) {
            $parents += array($parent->id => $parent) +
                $parent->get_all_parents();
        }
        return array_reverse($parents, true);
    }
    
    /**
     * This function recursively travels the categories, building up a nice list
     * for display or to use in a form <select> element
     *
     * For example, if you have a tree of categories like:
     *   Miscellaneous (id = 1)
     *      Subcategory (id = 2)
     *         Sub-subcategory (id = 4)
     *   Other category (id = 3)
     * Then after calling this function you will have
     * array(1 => 'Miscellaneous', 2 => 'Miscellaneous / Subcategory',
     *      4 => 'Miscellaneous / Subcategory / Sub-subcategory',
     *      3 => 'Other category');
     *
     * If you specify $requiredcapability, then only categories where the current
     * user has that capability will be added to $list.
     * If you only have $requiredcapability in a child category, not the parent,
     * then the child catgegory will still be included.
     *
     * If you specify the option $excludeid, then that category, and all its children,
     * are omitted from the tree. This is useful when you are doing something like
     * moving categories, where you do not want to allow people to move a category
     * to be the child of itself.
     *
     * @param string/array $requiredcapability if given, only categories where the current
     *      user has this capability will be added to $list. Can also be an array of capabilities,
     *      in which case they are all required.
     * @param integer $excludeid Omit this category and its children from the lists built.
     * @param string $separator string to use as a separator between parent and child category. Default ' / '
     * @param string $pathprefix For internal use, as part of recursive calls
     * @return array of strings
     */
    public static function make_categories_list($requiredcapability = '', $excludeid = 0, $separator = ' / ') {
        return self::get(0, MUST_EXIST, true)->get_children_names($requiredcapability, $excludeid, $separator);
    }

    /**
     * Helper function for {@link coursecat::make_categories_list()}
     *
     * @param string/array $requiredcapability if given, only categories where the current
     *      user has this capability will be added to $list. Can also be an array of capabilities,
     *      in which case they are all required.
     * @param integer $excludeid Omit this category and its children from the lists built.
     * @param string $separator string to use as a separator between parent and child category. Default ' / '
     * @param string $pathprefix For internal use, as part of recursive calls
     * @return array of strings
     */
    protected function get_children_names($requiredcapability = '', $excludeid = 0, $separator = ' / ', $pathprefix = '') {
        $list = array();        
        if ($excludeid && $this->id == $excludeid) {
            return $list;
        }

        if ($this->id) {
            // Update $path.
            if ($pathprefix) {
                $pathprefix .= $separator;
            }
            $pathprefix .= $this->get_formatted_name();

            // Add this category to $list, if the permissions check out.
            if (empty($requiredcapability) ||
                    has_all_capabilities((array)$requiredcapability, context_coursecat::instance($this->id))) {
                $list[$this->id] = $pathprefix;
            }
        }

        // Add all the children recursively, while updating the parents array.
        foreach ($this->get_children() as $cat) {
            $list += $cat->get_children_names($requiredcapability, $excludeid, $separator, $pathprefix);
        }
        
        return $list;
    }

    /**
     * Call to reset caches after any modification of course categories
     */
    public static function purge_cache() {
        $coursecatcache = cache::make('core', 'coursecat');
        $coursecatcache->purge();
    }

    // ====== implementing method from interface cacheable_object ======

    /**
     * Prepares the object for caching. Works like the __sleep method.
     *
     * @return array ready to be cached
     */
    public function prepare_to_cache() {
        $a = array(); 
        foreach (self::$coursecatfields as $property => $cachedirectives) {
            if ($cachedirectives !== null) {
                list($shortname, $defaultvalue) = $cachedirectives;
                if ($this->$property !== $defaultvalue) {
                    $a[$shortname] = $this->$property;
                }
            }
        }
        $context = context_coursecat::instance($this->id);
        foreach (self::$contextfields as $property => $cachedirectives) {
            if ($cachedirectives !== null) {
                list($shortname, $defaultvalue) = $cachedirectives;
                if ($context->$property !== $defaultvalue) {
                    $a[$shortname] = $context->$property;
                }
            }
        }
        self::mylog("prepare to cache ".print_r($a,true));
        return $a;
    }

    /**
     * Takes the data provided by prepare_to_cache and reinitialises an instance of the associated from it.
     *
     * @param array $a
     * @return coursecat
     */
    public static function wake_from_cache($a) {
        self::mylog("waking from cache ".print_r($a,true));
        $record = new stdClass;
        foreach (self::$coursecatfields as $property => $cachedirectives) {
            if ($cachedirectives !== null) {
                list($shortname, $defaultvalue) = $cachedirectives;
                if (array_key_exists($shortname, $a)) {
                    $record->$property = $a[$shortname];
                } else {
                    $record->$property = $defaultvalue;
                }
            }
        }
        foreach (self::$contextfields as $property => $cachedirectives) {
            if ($cachedirectives !== null) {
                list($shortname, $defaultvalue) = $cachedirectives;
                if (array_key_exists($shortname, $a)) {
                    $record->{'ctx'. $property} = $a[$shortname];
                } else {
                    $record->{'ctx'. $property} = $defaultvalue;
                }
            } else if ($property === 'contextlevel' && $record->id) {
                $record->ctxcontextlevel = CONTEXT_COURSECAT;
            } else if ($property === 'instanceid' && $record->id) {
                $record->ctxinstanceid = $record->id;
            }
        }
        return new coursecat($record, true);
    }
    
    public static function mylog($txt) {
        //global $DB;
        //$DB->execute("INSERT INTO {mylog} (timestamp, data) values (?, ?)", 
        //        array(time(), $txt));
    }
}

class coursecat_list implements cacheable_object {
    var $list;
    
    public function __construct($list) {
        $this->list = $list;
    }
    public function toArray() {
        return $this->list;
    }
    /**
     * Prepares the object for caching. Works like the __sleep method.
     *
     * @return mixed ready to be cached
     */
    public function prepare_to_cache() {
        $rv = array();
        foreach ($this->list as $key => $value) {
            if ($value instanceof cacheable_object) {
                $rv[$key] = new cache_cached_object($value);
            } else {
                $rv[$key] = $value;
            }
        }
        return $rv;
    }
    
    /**
     * Takes the data provided by prepare_to_cache and reinitialises an instance of the associated from it.
     *
     * @param string $data
     * @return coursecat_list
     */
    public static function wake_from_cache($data) {
        $list = array();
        foreach ($data as $key => $value) {
            if ($value instanceof cache_cached_object) {
                $list[$key] = $value->restore_object();
            } else {
                $list[$key] = $value;
            }
        }
        return new coursecat_list($list);
    }
}