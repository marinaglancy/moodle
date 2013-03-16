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
 * Contains class coursecat reponsible for course category operations
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
        'description' => null, // not cached
        'descriptionformat' => null, // not cached
        'parent' => array('pa', 0),
        'sortorder' => array('so', 0),
        'coursecount' => null, // not cached
        'visible' => array('vi', 1),
        'visibleold' => null, // not cached
        'timemodified' => null, // not cached
        'depth' => array('dh', 1),
        'path' => array('ph', null),
        'theme' => null, // not cached
    );

    /** @var int */
    protected $id;

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $idnumber = null;

    /** @var string */
    protected $description = false;

    /** @var int */
    protected $descriptionformat = false;

    /** @var int */
    protected $parent = 0;

    /** @var int */
    protected $sortorder = 0;

    /** @var int */
    protected $coursecount = false;

    /** @var int */
    protected $visible = 1;

    /** @var int */
    protected $visibleold = false;

    /** @var int */
    protected $timemodified = false;

    /** @var int */
    protected $depth = 0;

    /** @var string */
    protected $path = '';

    /** @var string */
    protected $theme = false;

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
     * Magic method getter, redirects to read only values. Queries from DB the fields that were not cached
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        global $DB;
        if (array_key_exists($name, self::$coursecatfields)) {
            if ($this->$name === false) {
                // property was not retrieved from DB
                if ($name === 'description' || $name === 'descriptionformat') {
                    // usually if one field is requested another one will be requested shortly
                    $record = $DB->get_record('course_categories', array('id' => $this->id), 'description, descriptionformat', MUST_EXIST);
                    $this->description = $record->description;
                    $this->descriptionformat = $record->descriptionformat;
                } else {
                    $this->$name = $DB->get_field('course_categories', $name, array('id' => $this->id), MUST_EXIST);
                }
            }
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
            if ($this->$property !== false) {
                $ret[$property] = $this->$property;
            }
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
     * unless $alwaysreturnhidden is set to true
     *
     * If id is 0, the pseudo object for root category is returned (convenient
     * for calling other functions such as get_children())
     *
     * @param int $id category id
     * @param int $strictness whether to throw an exception (MUST_EXIST) or
     *     return null (IGNORE_MISSING) in case the category is not found or
     *     not visible to current user
     * @param bool $alwaysreturnhidden set to true if you want an object to be
     *     returned even if this category is not visible to the current user
     *     (category is hidden and user does not have
     *     'moodle/category:viewhiddencategories' capability). Use with care!
     * @return null|coursecat
     */
    public static function get($id, $strictness = MUST_EXIST, $alwaysreturnhidden = false) {
        global $DB;
        if (!$id) {
            if (!isset(self::$coursecat0)) {
                $record = new stdClass();
                $record->id = 0;
                $record->visible = 1;
                $record->depth = 0;
                $record->path = '';
                self::$coursecat0 = new coursecat($record);
            }
            return self::$coursecat0;
        }
        $coursecatcache = cache::make('core', 'coursecat');
        $coursecat = $coursecatcache->get($id);
        if ($coursecat === false) {
            $all = self::get_all_ids();
            if (array_key_exists($id, $all)) {
                // Retrieve from DB only the fields that need to be stored in cache
                $fields = array_filter(array_keys(self::$coursecatfields), function ($element)
                    { return (self::$coursecatfields[$element] !== null); } );
                $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
                $sql = "SELECT cc.". join(',cc.', $fields). ", $ctxselect
                        FROM {course_categories} cc
                        JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = ?
                        WHERE cc.id = ?";
                if ($record = $DB->get_record_sql($sql, array(CONTEXT_COURSECAT, $id))) {
                    $coursecat = new coursecat($record);
                    // Store in cache
                    $coursecatcache->set($id, $coursecat);
                }
            }
        }
        if ($coursecat && ($alwaysreturnhidden || $coursecat->is_uservisible())) {
            return $coursecat;
        } else {
            if ($strictness == MUST_EXIST) {
                throw new moodle_exception('unknowcategory');
            }
        }
        return null;
    }

    /**
     * Returns the first found category
     *
     * Note that if there are no categories visible to the current user on the first level,
     * the invisible category may be returned
     *
     * @return coursecat
     */
    public static function get_default() {
        if ($visiblechildren = self::get(0)->get_children()) {
            $defcategory = reset($visiblechildren);
        } else {
            $all = $this->get_all_ids();
            $defcategoryid = $all[0][0];
            $defcategory = self::get($defcategoryid, MUST_EXIST, true);
        }
        return $defcategory;
    }

    /**
     * Restores the object after it has been externally modified in DB for example
     * during {@link fix_course_sortorder()}
     */
    protected function restore() {
        // update all fields in the current object
        $newrecord = self::get($this->id, MUST_EXIST, true);
        foreach (self::$coursecatfields as $key => $unused) {
            $this->$key = $newrecord->$key;
        }
    }

    /**
     * Creates a new category either from form data or from raw data
     *
     * Please note that this function does not verify access control.
     *
     * Exception is thrown if name is missing or idnumber is duplicating another one in the system.
     *
     * Category visibility is inherited from parent unless $data->visible = 0 is specified
     *
     * @param array|stdClass $data
     * @param array $editoroptions if specified, the data is considered to be
     *    form data and file_postupdate_standard_editor() is being called to
     *    process images in description.
     * @return coursecat
     * @throws moodle_exception
     */
    public static function create($data, $editoroptions = null) {
        global $DB, $CFG;
        $data = (object)$data;
        $newcategory = new stdClass();

        $newcategory->descriptionformat = FORMAT_MOODLE;
        $newcategory->description = '';
        // copy all description* fields regardless of whether this is form data or direct field update
        foreach ($data as $key => $value) {
            if (preg_match("/^description/", $key)) {
                $newcategory->$key = $value;
            }
        }

        if (empty($data->name)) {
            throw new moodle_exception('categorynamerequired');
        }
        if (textlib::strlen($data->name) > 255) {
            throw new moodle_exception('categorytoolong');
        }
        $newcategory->name = $data->name;

        // validate and set idnumber
        if (!empty($data->idnumber)) {
            if ($existing = $DB->get_record('course_categories', array('idnumber' => $data->idnumber))) {
                throw new moodle_exception('categoryidnumbertaken');
            }
            if (textlib::strlen($data->idnumber) > 100) {
                throw new moodle_exception('idnumbertoolong');
            }
        }
        if (isset($data->idnumber)) {
            $newcategory->idnumber = $data->idnumber;
        }

        if (isset($data->theme) && !empty($CFG->allowcategorythemes)) {
            $newcategory->theme = $data->theme;
        }

        if (empty($data->parent)) {
            $parent = self::get(0);
        } else {
            $parent = self::get($data->parent, MUST_EXIST, true);
        }
        $newcategory->parent = $parent->id;
        $newcategory->depth = $parent->depth + 1;

        // By default category is visible, unless visible = 0 is specified or parent category is hidden
        if (isset($data->visible) && !$data->visible) {
            // create a hidden category
            $newcategory->visible = $newcategory->visibleold = 0;
        } else {
            // create a category that inherits visibility from parent
            $newcategory->visible = $parent->visible;
            // in case parent is hidden, when it changes visibility this new subcategory will automatically become visible too
            $newcategory->visibleold = 1;
        }

        $newcategory->sortorder = 0;
        $newcategory->timemodified = time();

        $newcategory->id = $DB->insert_record('course_categories', $newcategory);

        // update path (only possible after we know the category id
        $path = $parent->path . '/' . $newcategory->id;
        $DB->set_field('course_categories', 'path', $path, array('id' => $newcategory->id));

        // We should mark the context as dirty
        context_coursecat::instance($newcategory->id)->mark_dirty();

        fix_course_sortorder();

        // if this is data from form results, save embedded files and update description
        $categorycontext = context_coursecat::instance($newcategory->id);
        if ($editoroptions) {
            $newcategory = file_postupdate_standard_editor($newcategory, 'description', $editoroptions, $categorycontext, 'coursecat', 'description', 0);

            // update only fields description and descriptionformat
            $updatedata = array_intersect_key((array)$newcategory, array('id' => 1, 'description' => 1, 'descriptionformat' => 1));
            $DB->update_record('course_categories', $updatedata);

            self::purge_cache();
        }

        add_to_log(SITEID, "category", 'add', "editcategory.php?id=$newcategory->id", $newcategory->id);

        return self::get($newcategory->id, MUST_EXIST, true);
    }

    /**
     * Updates the record with either form data or raw data
     *
     * Please note that this function does not verify access control.
     *
     * This function calls coursecat::change_parent_raw if field 'parent' is updated.
     * It also calls coursecat::hide_raw or coursecat::show_raw if 'visible' is updated.
     * Visibility is changed first and then parent is changed. This means that
     * if parent category is hidden, the current category will become hidden
     * too and it may overwrite whatever was set in field 'visible'.
     *
     * Note that fields 'path' and 'depth' can not be updated manually
     * Also coursecat::update() can not directly update the field 'sortoder'
     *
     * @param array|stdClass $data
     * @param array $editoroptions if specified, the data is considered to be
     *    form data and file_postupdate_standard_editor() is being called to
     *    process images in description.
     * @throws moodle_exception
     */
    public function update($data, $editoroptions = null) {
        global $DB, $CFG;
        if (!$this->id) {
            // there is no actual DB record associated with root category
            return;
        }

        $data = (object)$data;
        $newcategory = new stdClass();
        $newcategory->id = $this->id;

        // copy all description* fields regardless of whether this is form data or direct field update
        foreach ($data as $key => $value) {
            if (preg_match("/^description/", $key)) {
                $newcategory->$key = $value;
            }
        }

        if (isset($data->name) && empty($data->name)) {
            throw new moodle_exception('categorynamerequired');
        }

        if (!empty($data->name) && $data->name !== $this->name) {
            if (textlib::strlen($data->name) > 255) {
                throw new moodle_exception('categorytoolong');
            }
            $newcategory->name = $data->name;
        }

        if (isset($data->idnumber) && $data->idnumber != $this->idnumber) {
            if (textlib::strlen($data->idnumber) > 100) {
                throw new moodle_exception('idnumbertoolong');
            }
            if ($existing = $DB->get_record('course_categories', array('idnumber' => $data->idnumber))) {
                throw new moodle_exception('categoryidnumbertaken');
            }
            $newcategory->idnumber = $data->idnumber;
        }

        if (isset($data->theme) && !empty($CFG->allowcategorythemes)) {
            $newcategory->theme = $data->theme;
        }

        $changes = false;
        if (isset($data->visible)) {
            if ($data->visible) {
                $changes = $this->show_raw();
            } else {
                $changes = $this->hide_raw(0);
            }
        }

        if (isset($data->parent) && $data->parent != $this->parent) {
            if ($changes) {
                self::purge_cache();
            }
            $parentcat = self::get($data->parent, MUST_EXIST, true);
            $this->change_parent_raw($parentcat);
            fix_course_sortorder();
        }

        $newcategory->timemodified = time();

        if ($editoroptions) {
            $categorycontext = context_coursecat::instance($this->id);
            $newcategory = file_postupdate_standard_editor($newcategory, 'description', $editoroptions, $categorycontext, 'coursecat', 'description', 0);
        }
        $DB->update_record('course_categories', $newcategory);
        add_to_log(SITEID, "category", 'update', "editcategory.php?id=$this->id", $this->id);
        fix_course_sortorder();

        // update all fields in the current object
        $this->restore();
    }

    /**
     * Checks if this course category is visible to current user
     *
     * Please note that methods coursecat::get (without 3rd argumet),
     * coursecat::get_children(), etc. return only visible categories so it is
     * usually not needed to call this function outside of this class
     *
     * @return bool
     */
    public function is_uservisible() {
        return !$this->id || $this->visible ||
                has_capability('moodle/category:viewhiddencategories',
                        context_coursecat::instance($this->id));
    }

    /**
     * Returns the complete corresponding record from DB table course_categories
     *
     * Mostly used in deprecated functions
     *
     * @return stdClass
     */
    public function get_db_record() {
        global $DB;
        if ($record = $DB->get_record('course_categories', array('id' => $this->id))) {
            return $record;
        } else {
            return (object)convert_to_array($this);
        }
    }

    /**
     * Returns tree of categories ids
     *
     * Return array has categories ids as keys and list of children ids as values.
     * Also there is an additional first element with key 0 with list of categories on the top level.
     * Therefore the number of elements in the return array is one more than number of categories in the system.
     *
     * Also this method ensures that all categories are cached together with their contexts.
     * 
     * @return array
     */
    protected static function get_all_ids() {
        global $DB;
        $coursecatcache = cache::make('core', 'coursecat');
        $all = $coursecatcache->get('all');
        if ($all === false) {
            $coursecatcache->purge(); // it should be empty already but to be extra sure
            $sql = "SELECT cc.id, cc.parent
                    FROM {course_categories} cc
                    ORDER BY cc.sortorder";
            $rs = $DB->get_recordset_sql($sql, array());
            $all = array(0 => array());
            foreach ($rs as $record) {
                $all[$record->id] = array();
                $all[$record->parent][] = $record->id;
            }
            $rs->close();
            if (!count($all[0])) {
                // No categories found.
                // This may happen after upgrade from very old moodle version. In new versions the default category is created on install.
                $defcoursecat = self::create(array('name' => get_string('miscellaneous')));
                $coursecatcache->set($defcoursecat->id, $defcoursecat);
                set_config('defaultrequestcategory', $defcoursecat->id);
                $all[0][$defcoursecat->id] = array();
            }
            $coursecatcache->set('all', $all);
        }
        return $all;
    }

    /**
     * Returns number of ALL categories in the system regardless if
     * they are visible to current user or not
     *
     * @return int
     */
    public static function count_all() {
        $all = self::get_all_ids();
        return count($all) - 1; // do not count 0-category
    }

    /**
     * Returns array of children categories visible to the current user
     *
     * @return array of coursecat objects indexed by category id
     */
    public function get_children() {
        $all = self::get_all_ids();
        $rv = array();
        if (!empty($all[$this->id])) {
            foreach ($all[$this->id] as $id) {
                if ($coursecat = self::get($id, IGNORE_MISSING)) {
                    // do not return invisible
                    $rv[$coursecat->id] = $coursecat;
                }
            }
        }
        return $rv;
    }

    /**
     * Returns true if the category has ANY children, including those not visible to the user
     *
     * @return boolean
     */
    public function has_children() {
        $all = self::get_all_ids();
        return !empty($all[$this->id]);
    }

    /**
     * Returns true if the category has courses in it (count does not include courses
     * in child categories)
     *
     * @return bool
     */
    public function has_courses() {
        global $DB;
        return $DB->record_exists_sql("select 1 from {course} where category = ?",
                array($this->id));
    }

    /**
     * Returns true if user can delete current category and all its contents
     *
     * To be able to delete course category the user must have permission
     * 'moodle/category:manage' in ALL child course categories AND
     * be able to delete all courses
     *
     * @return bool
     */
    public function can_delete_full() {
        global $DB;
        if (!$this->id) {
            // fool-proof
            return false;
        }

        $context = context_coursecat::instance($this->id);
        if (!$this->is_uservisible() ||
                !has_capability('moodle/category:manage', $context)) {
            return false;
        }

        // Check all child categories (not only direct children)
        $sql = context_helper::get_preload_record_columns_sql('ctx');
        $childcategories = $DB->get_records_sql('SELECT c.id, c.visible, '. $sql.
            ' FROM {context} ctx '.
            ' JOIN {course_categories} c ON c.id = ctx.instanceid'.
            ' WHERE ctx.path like ? AND ctx.contextlevel = ?',
                array($context->path. '/%', CONTEXT_COURSECAT));
        foreach ($childcategories as $childcat) {
            context_helper::preload_from_record($childcat);
            $childcontext = context_coursecat::instance($childcat->id);
            if ((!$childcat->visible && !has_capability('moodle/category:viewhiddencategories', $childcontext)) ||
                    !has_capability('moodle/category:manage', $childcontext)) {
                return false;
            }
        }

        // Check courses
        $sql = context_helper::get_preload_record_columns_sql('ctx');
        $coursescontexts = $DB->get_records_sql('SELECT ctx.instanceid AS courseid, '.
                    $sql. ' FROM {context} ctx '.
                    'WHERE ctx.path like :pathmask and ctx.contextlevel = :courselevel',
                array('pathmask' => $context->path. '/%',
                    'courselevel' => CONTEXT_COURSE));
        foreach ($coursescontexts as $ctxrecord) {
            context_helper::preload_from_record($ctxrecord);
            if (!can_delete_course($ctxrecord->courseid)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively delete category including all subcategories and courses
     *
     * Function {@link coursecat::can_delete_full()} MUST be called prior
     * to calling this function because there is no capability check
     * inside this function
     *
     * @param boolean $showfeedback display some notices
     * @return array return deleted courses
     */
    public function delete_full($showfeedback = true) {
        global $CFG, $DB;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/questionlib.php');
        require_once($CFG->dirroot.'/cohort/lib.php');

        $deletedcourses = array();

        // Get children. Note, we don't want to use cache here because
        // it would be rebuilt too often
        $children = $DB->get_records('course_categories', array('parent' => $this->id), 'sortorder ASC');
        foreach ($children as $record) {
            $coursecat = new coursecat($record);
            $deletedcourses += $coursecat->delete_full($showfeedback);
        }

        if ($courses = $DB->get_records('course', array('category' => $this->id), 'sortorder ASC')) {
            foreach ($courses as $course) {
                if (!delete_course($course, false)) {
                    throw new moodle_exception('cannotdeletecategorycourse', '', '', $course->shortname);
                }
                $deletedcourses[] = $course;
            }
        }

        // move or delete cohorts in this context
        cohort_delete_category($this);

        // now delete anything that may depend on course category context
        grade_course_category_delete($this->id, 0, $showfeedback);
        if (!question_delete_course_category($this, 0, $showfeedback)) {
            throw new moodle_exception('cannotdeletecategoryquestions', '', '', $this->get_formatted_name());
        }

        // finally delete the category and it's context
        $DB->delete_records('course_categories', array('id' => $this->id));
        delete_context(CONTEXT_COURSECAT, $this->id);
        add_to_log(SITEID, "category", "delete", "index.php", "$this->name (ID $this->id)");

        self::purge_cache();

        events_trigger('course_category_deleted', $this);

        // If we deleted $CFG->defaultrequestcategory, make it point somewhere else.
        if ($this->id == $CFG->defaultrequestcategory) {
            set_config('defaultrequestcategory', $DB->get_field('course_categories', 'MIN(id)', array('parent' => 0)));
        }
        return $deletedcourses;
    }

    /**
     * Checks if user can delete this category and move content (courses, subcategories and questions)
     * to another category. If yes returns the array of possible target categories names
     *
     * If user can not manage this category or it is completely empty - empty array will be returned
     *
     * @return array
     */
    public function move_content_targets_list() {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');
        $context = context_coursecat::instance($this->id);
        if (!$this->is_uservisible() ||
                !has_capability('moodle/category:manage', $context)) {
            // User is not able to manage current category, he is not able to delete it.
            // No possible target categories.
            return array();
        }

        $testcaps = array();
        // If this category has courses in it, user must have 'course:create' capability in target category.
        if ($this->has_courses()) {
            $testcaps[] = 'moodle/course:create';
        }
        // If this category has subcategories or questions, user must have 'category:manage' capability in target category.
        if ($this->has_children() || question_context_has_any_questions($context)) {
            $testcaps[] = 'moodle/category:manage';
        }
        if (!empty($testcaps)) {
            // return list of categories excluding this one and it's children
            return self::make_categories_list($testcaps, $this->id);
        }

        // Category is completely empty, no need in target for contents.
        return array();
    }

    /**
     * Checks if user has capability to move all category content to the new parent before
     * removing this category
     *
     * @param int $newcatid
     * @return bool
     */
    public function can_move_content_to($newcatid) {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');
        $context = context_coursecat::instance($this->id);
        if (!$this->is_uservisible() ||
                !has_capability('moodle/category:manage', $context)) {
            return false;
        }
        $testcaps = array();
        // If this category has courses in it, user must have 'course:create' capability in target category.
        if ($this->has_courses()) {
            $testcaps[] = 'moodle/course:create';
        }
        // If this category has subcategories or questions, user must have 'category:manage' capability in target category.
        if ($this->has_children() || question_context_has_any_questions($context)) {
            $testcaps[] = 'moodle/category:manage';
        }
        if (!empty($testcaps)) {
            return has_all_capabilities($testcaps, context_coursecat::instance($newcatid));
        }

        // there is no content but still return true
        return true;
    }

    /**
     * Deletes a category and moves all content (children, courses and questions) to the new parent
     *
     * Note that this function does not check capabilities, {@link coursecat::can_move_content_to()}
     * must be called prior
     *
     * @param int $newparentid
     * @param bool $showfeedback
     * @return bool
     */
    public function delete_move($newparentid, $showfeedback = false) {
        global $CFG, $DB, $OUTPUT;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/questionlib.php');
        require_once($CFG->dirroot.'/cohort/lib.php');

        // get all objects and lists because later the caches will be reset so
        // we don't need to make extra queries
        $newparentcat = self::get($newparentid, MUST_EXIST, true);
        $catname = $this->get_formatted_name();
        $children = $this->get_children();
        $coursesids = $DB->get_fieldset_select('course', 'id', 'category = :category ORDER BY sortorder ASC', array('category' => $this->id));
        $context = context_coursecat::instance($this->id);

        if ($children) {
            foreach ($children as $childcat) {
                $childcat->change_parent_raw($newparentcat);
                // Log action.
                add_to_log(SITEID, "category", "move", "editcategory.php?id=$childcat->id", $childcat->id);
            }
            fix_course_sortorder();
        }

        if ($coursesids) {
            if (!move_courses($coursesids, $newparentid)) {
                if ($showfeedback) {
                    echo $OUTPUT->notification("Error moving courses");
                }
                return false;
            }
            if ($showfeedback) {
                echo $OUTPUT->notification(get_string('coursesmovedout', '', $catname), 'notifysuccess');
            }
        }

        // move or delete cohorts in this context
        cohort_delete_category($this);

        // now delete anything that may depend on course category context
        grade_course_category_delete($this->id, $newparentid, $showfeedback);
        if (!question_delete_course_category($this, $newparentcat, $showfeedback)) {
            if ($showfeedback) {
                echo $OUTPUT->notification(get_string('errordeletingquestionsfromcategory', 'question', $catname), 'notifysuccess');
            }
            return false;
        }

        // finally delete the category and it's context
        $DB->delete_records('course_categories', array('id' => $this->id));
        $context->delete();
        add_to_log(SITEID, "category", "delete", "index.php", "$this->name (ID $this->id)");

        events_trigger('course_category_deleted', $this);

        self::purge_cache();

        if ($showfeedback) {
            echo $OUTPUT->notification(get_string('coursecategorydeleted', '', $catname), 'notifysuccess');
        }

        // If we deleted $CFG->defaultrequestcategory, make it point somewhere else.
        if ($this->id == $CFG->defaultrequestcategory) {
            set_config('defaultrequestcategory', $DB->get_field('course_categories', 'MIN(id)', array('parent' => 0)));
        }
        return true;
    }

    /**
     * Checks if user can move current category to the new parent
     *
     * This checks if new parent category exists, user has manage cap there
     * and new parent is not a child of this category
     *
     * @param int|stdClass|coursecat $newparentcat
     * @return bool
     */
    public function can_change_parent($newparentcat) {
        if (!has_capability('moodle/category:manage', context_coursecat::instance($this->id))) {
            return false;
        }
        if (is_object($newparentcat)) {
            $newparentcat = self::get($newparentcat->id, IGNORE_MISSING);
        } else {
            $newparentcat = self::get((int)$newparentcat, IGNORE_MISSING);
        }
        if (!$newparentcat) {
            return false;
        }
        if ($newparentcat->id == $this->id || in_array($this->id, $newparentcat->get_parents())) {
            // can not move to itself or it's own child
            return false;
        }
        return has_capability('moodle/category:manage', get_category_or_system_context($newparentcat->id));
    }

    /**
     * Moves the category under another parent category. All associated contexts are moved as well
     *
     * This is protected function, use change_parent() or update() from outside of this class
     *
     * @see coursecat::change_parent()
     * @see coursecat::update()
     *
     * @param coursecat $newparentcat
     */
     protected function change_parent_raw(coursecat $newparentcat) {
        global $DB;

        $context = context_coursecat::instance($this->id);

        $hidecat = false;
        if (empty($newparentcat->id)) {
            $DB->set_field('course_categories', 'parent', 0, array('id' => $this->id));
            $newparent = context_system::instance();
        } else {
            if ($newparentcat->id == $this->id || in_array($this->id, $newparentcat->get_parents())) {
                // can not move to itself or it's own child
                throw new moodle_exception('cannotmovecategory');
            }
            $DB->set_field('course_categories', 'parent', $newparentcat->id, array('id' => $this->id));
            $newparent = context_coursecat::instance($newparentcat->id);

            if (!$newparentcat->visible and $this->visible) {
                // better hide category when moving into hidden category, teachers may unhide afterwards and the hidden children will be restored properly
                $hidecat = true;
            }
        }
        $this->parent = $newparentcat->id;

        context_moved($context, $newparent);

        // now make it last in new category
        $DB->set_field('course_categories', 'sortorder', MAX_COURSES_IN_CATEGORY*MAX_COURSE_CATEGORIES, array('id' => $this->id));

        if ($hidecat) {
            fix_course_sortorder();
            $this->restore();
            // Hide object but store 1 in visibleold, because when parent category visibility changes this category must become visible again.
            $this->hide_raw(1);
        }
    }

    /**
     * Efficiently moves a category - NOTE that this can have
     * a huge impact access-control-wise...
     *
     * Note that this function does not check capabilities.
     *
     * Example of usage:
     * $coursecat = coursecat::get($categoryid);
     * if ($coursecat->can_change_parent($newparentcatid)) {
     *     $coursecat->change_parent($newparentcatid);
     * }
     *
     * This function does not update field course_categories.timemodified
     * If you want to update timemodified, use
     * $coursecat->update(array('parent' => $newparentcat));
     *
     * @param int|stdClass|coursecat $newparentcat
     */
    public function change_parent($newparentcat) {
        // Make sure parent category exists but do not check capabilities here that it is visible to current user.
        if (is_object($newparentcat)) {
            $newparentcat = self::get($newparentcat->id, MUST_EXIST, true);
        } else {
            $newparentcat = self::get((int)$newparentcat, MUST_EXIST, true);
        }
        if ($newparentcat->id != $this->parent) {
            $this->change_parent_raw($newparentcat);
            fix_course_sortorder();
            $this->restore();
            add_to_log(SITEID, "category", "move", "editcategory.php?id=$this->id", $this->id);
        }
    }

    /**
     * Hide course category and child course and subcategories
     *
     * If this category has changed the parent and is moved under hidden
     * category we will want to store it's current visibility state in
     * the field 'visibleold'. If admin clicked 'hide' for this particular
     * category, the field 'visibleold' should become 0.
     *
     * All subcategories and courses will have their current visibility in the field visibleold
     *
     * This is protected function, use hide() or update() from outside of this class
     *
     * @see coursecat::hide()
     * @see coursecat::update()
     *
     * @param int $visibleold value to set in field $visibleold for this category
     * @return bool whether changes have been made and caches need to be purged afterwards
     */
    protected function hide_raw($visibleold = 0) {
        global $DB;
        $changes = false;

        // Note that field 'visibleold' is not cached so we must retrieve it from DB if it is missing
        if ($this->id && $this->__get('visibleold') != $visibleold) {
            $this->visibleold = $visibleold;
            $DB->set_field('course_categories', 'visibleold', $visibleold, array('id' => $this->id));
            $changes = true;
        }
        if (!$this->visible || !$this->id) {
            // already hidden or can not be hidden
            return $changes;
        }

        $this->visible = 0;
        $DB->set_field('course_categories', 'visible', 0, array('id'=>$this->id));
        $DB->execute("UPDATE {course} SET visibleold = visible WHERE category = ?", array($this->id)); // store visible flag so that we can return to it if we immediately unhide
        $DB->set_field('course', 'visible', 0, array('category' => $this->id));
        // get all child categories and hide too
        if ($subcats = $DB->get_records_select('course_categories', "path LIKE ?", array("$this->path/%"), 'id, visible')) {
            foreach ($subcats as $cat) {
                $DB->set_field('course_categories', 'visibleold', $cat->visible, array('id' => $cat->id));
                $DB->set_field('course_categories', 'visible', 0, array('id' => $cat->id));
                $DB->execute("UPDATE {course} SET visibleold = visible WHERE category = ?", array($cat->id));
                $DB->set_field('course', 'visible', 0, array('category' => $cat->id));
            }
        }
        return true;
    }

    /**
     * Hide course category and child course and subcategories
     *
     * Note that there is no capability check inside this function
     *
     * This function does not update field course_categories.timemodified
     * If you want to update timemodified, use
     * $coursecat->update(array('visible' => 0));
     */
    public function hide() {
        if ($this->hide_raw(0)) {
            self::purge_cache();
            add_to_log(SITEID, "category", "hide", "editcategory.php?id=$this->id", $this->id);
        }
    }

    /**
     * Show course category and restores visibility for child course and subcategories
     *
     * Note that there is no capability check inside this function
     *
     * This is protected function, use show() or update() from outside of this class
     *
     * @see coursecat::show()
     * @see coursecat::update()
     *
     * @return bool whether changes have been made and caches need to be purged afterwards
     */
    protected function show_raw() {
        global $DB;

        if ($this->visible) {
            // already visible
            return false;
        }

        $this->visible = 1;
        $this->visibleold = 1;
        $DB->set_field('course_categories', 'visible', 1, array('id' => $this->id));
        $DB->set_field('course_categories', 'visibleold', 1, array('id' => $this->id));
        $DB->execute("UPDATE {course} SET visible = visibleold WHERE category = ?", array($this->id));
        // get all child categories and unhide too
        if ($subcats = $DB->get_records_select('course_categories', "path LIKE ?", array("$this->path/%"), 'id, visibleold')) {
            foreach ($subcats as $cat) {
                if ($cat->visibleold) {
                    $DB->set_field('course_categories', 'visible', 1, array('id' => $cat->id));
                }
                $DB->execute("UPDATE {course} SET visible = visibleold WHERE category = ?", array($cat->id));
            }
        }
        return true;
    }

    /**
     * Show course category and restores visibility for child course and subcategories
     *
     * Note that there is no capability check inside this function
     *
     * This function does not update field course_categories.timemodified
     * If you want to update timemodified, use
     * $coursecat->update(array('visible' => 1));
     */
    public function show() {
        if ($this->show_raw()) {
            self::purge_cache();
            add_to_log(SITEID, "category", "show", "editcategory.php?id=$this->id", $this->id);
        }
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
     * Returns ids of all parents of the category. Last element in the return array is the direct parent
     *
     * For example, if you have a tree of categories like:
     *   Miscellaneous (id = 1)
     *      Subcategory (id = 2)
     *         Sub-subcategory (id = 4)
     *   Other category (id = 3)
     *
     * coursecat::get(1)->get_parents() == array()
     * coursecat::get(2)->get_parents() == array(1)
     * coursecat::get(4)->get_parents() == array(1, 2);
     *
     * Note that this method does not check if all parents are accessible by current user
     *
     * @return array of category ids
     */
    public function get_parents() {
        $parents = preg_split('|/|', $this->path, 0, PREG_SPLIT_NO_EMPTY);
        array_pop($parents);
        return $parents;
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
     * array(1 => 'Miscellaneous',
     *       2 => 'Miscellaneous / Subcategory',
     *       4 => 'Miscellaneous / Subcategory / Sub-subcategory',
     *       3 => 'Other category');
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
     * See also {@link make_categories_options()}
     *
     * @param string/array $requiredcapability if given, only categories where the current
     *      user has this capability will be returned. Can also be an array of capabilities,
     *      in which case they are all required.
     * @param integer $excludeid Exclude this category and its children from the lists built.
     * @param string $separator string to use as a separator between parent and child category. Default ' / '
     * @return array of strings
     */
    public static function make_categories_list($requiredcapability = '', $excludeid = 0, $separator = ' / ') {
        return self::get(0)->get_children_names($requiredcapability, $excludeid, $separator);
    }

    /**
     * Helper function for {@link coursecat::make_categories_list()}
     *
     * @param string/array $requiredcapability if given, only categories where the current
     *      user has this capability will be included in return value. Can also be
     *      an array of capabilities, in which case they are all required.
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
        $a['xi'] = $context->id;
        $a['xp'] = $context->path;
        return $a;
    }

    /**
     * Takes the data provided by prepare_to_cache and reinitialises an instance of the associated from it.
     *
     * @param array $a
     * @return coursecat
     */
    public static function wake_from_cache($a) {
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
        $record->ctxid = $a['xi'];
        $record->ctxpath = $a['xp'];
        $record->ctxdepth = $record->depth + 1;
        $record->ctxlevel = CONTEXT_COURSECAT;
        $record->ctxinstance = $record->id;
        return new coursecat($record, true);
    }
}
