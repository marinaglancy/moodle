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
 * Class representing course category
 *
 * Used for course category actions and displaying a category
 *
 * @package   core
 * @copyright 2013 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * The core course renderer
 *
 * @package   core
 * @copyright 2013 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_category implements renderable {
    protected $attributes = array();

    /** @var string attribute id : if specified, display subcategories and courses in this category (0 means top level) */
    const ID = 'id';
    /** @var string attribute : [none, countonly, collapsed, expanded] how (if) display courses list */
//    const DISPLAYCOURSES = 'display';
    /** @var string attribute : depth to expand subcategories in the tree (deeper subcategories will be loaded by AJAX or proceed to category page by clicking on category name) */
//    const EXPANDSUBCATEGORIESDEPTH = 'expanddepth'; // TODO rename to subcategorydepth for better understanding
    /** @var string attribute : for small sites, do not display categories names just list all courses in all subcategories */
    const OMITSUBCATEGORIES = 'omitcat';
    /** @var string attribute : how to sort courses */
    const SORTCOURSES = 'sort';
    /** @var string attribute : how to sort subcategories */
    const SORTCATEGORIES = 'sortcat';
    /** @var string attribute : limit the number of subcategories inside one category.
     * If there are more categories, a link "More categories..." is displayed,
     * which leads to the subcategory page, or displays the next page or loads
     * more entries via AJAX. Defaults to $CFG->coursesperpage.
     * Also can be concatenated with level: course_category::sortcategories.'2' */
//    const CATEGORIESLIMIT = 'limitcat';
    /** @var string attribute : limit the number of courses inside one category.
     * If there are more courses, a link "More courses..." is displayed which
     * leads to the subcategory page, or displays the next page or loads more
     * entries via AJAX. Defaults to $CFG->coursesperpage */
//    const COURSESLIMIT = 'limit';
    /** @var string attribute : completely disable AJAX loading even if browser
     * supports it */
//    const AJAXDISABLED = 'noajax';
    /** @var string attribute : add a heading (?) */
//    const HEADING = 'heading';
    /** @var string attribute : depth of this category in the current view */
    const DEPTH = 'depth';
    /** @var string attribute : search string in courses names and/or descriptions */
    const SEARCHSTRING = 'search';
    /** @var string attribute : display category name in course description
     * (may be used in search results or in 'my courses' lists) */
    const DISPLAYCATEGORYNAME = 'showcatname';

    /**
     * Constructor
     *
     * @param array $attributes array of category retrive/display attributes
     *     where keys are the constants defined above
     */
    public function __construct($attributes = array()) {
        global $CFG;
        if (empty($attributes)) {
            $attributes = array();
        }
        if (!is_array($attributes)) {
            $attributes = (array)$attributes;
        }
        // defaults:
        $defaults = array(
            self::DEPTH => 0,
            self::ID => 0,
//            self::CATEGORIESLIMIT => $CFG->coursesperpage,
//            self::COURSESLIMIT => $CFG->coursesperpage,
//            self::EXPANDSUBCATEGORIESDEPTH => 5,// TODO $CFG->maxcategorydepth
        );
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $attributes)) {
                $attributes[$key] = $value;
            }
        }
        $this->attributes = $attributes;
    }

    /**
     * Get the category attribute. Some attributes are substituted with
     * defaults or overwritten
     *
     * @param string $name
     * @return mixed
     */
    public function get_attr($name) {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return null;
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    public function set_attr($name, $value) {
        $this->attributes[$name] = $value;
    }

    /**
     * Magic method to check if attribute id is set
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        if ($name === 'id') {
            $v = $this->get_attr(self::ID);
            return isset($v);
        }
        return false;
    }

    /**
     * Magic method to get attribute 'id'
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'id') {
            return $this->get_attr(self::ID);
        }
    }

    protected static $categoriesbyparent = null;
    protected static $allcategories = null;

    /**
     * Returns the whole categories tree visible to current user.
     * Each element of the returned array is an object representing
     * DB row from table course_categories
     * 
     * @param bool $byparent categories returned in array indexed by
     *     parent category id
     * @return array
     */
    public static function get_categories($byparent = false) {
        if (self::$allcategories === null) {
            self::$allcategories = get_categories();
        }
        if (!$byparent) {
            return self::$allcategories;
        }

        // only fill in this variable the first time
        if (self::$categoriesbyparent === null) {
            self::$categoriesbyparent = array();
            foreach (self::$allcategories as $category) {
                if (empty(self::$categoriesbyparent[$category->parent])) {
                    self::$categoriesbyparent[$category->parent] = array();
                }
                self::$categoriesbyparent[$category->parent][$category->id] = $category;
            }
        }

        return self::$categoriesbyparent;
    }

    /**
     * Returns a category object representing DB row in course_categories
     *
     * @return stdClass
     */
    public function get_category() {
        if (!$this->id) {
            return (object)array('id' => 0);
        }
        $categories = self::get_categories();
        if (array_key_exists($this->id, $categories)) {
            return $categories[$this->id];
        }
        return null;
    }

    /**
     * Returns formatted and filtered name of the current category
     *
     * @param array $options format options, if context is not specified
     *     it will be added automatically
     * @return string|null name or null for the 0-category
     */
    public function get_formatted_name($options = array()) {
        $cat = $this->get_category();
        if ($cat->id && !empty($cat->name)) {
            if (!isset($options['context'])) {
                $options['context'] = context_coursecat::instance($cat->id);
            }
            return format_string($cat->name, true, $options);
        }
        return null;
    }

    /**
     * Returns formatted and filtered description of current category
     *
     * @param array $options format options, by default [noclean,overflowdiv],
     *     if context is not specified it will be added automatically
     * @return string|null
     */
    public function get_formatted_description($options = null) {
        $cat = $this->get_category();
        if ($cat->id && !empty($cat->description)) {
            if (!isset($cat->descriptionformat)) {
                $cat->descriptionformat = FORMAT_MOODLE;
            }
            if ($options === null) {
                $options = array('noclean' => true, 'overflowdiv' => true);
            }
            if (!isset($options['context'])) {
                $options['context'] = context_coursecat::instance($cat->id);
            }
            $text = file_rewrite_pluginfile_urls($cat->description,
                    'pluginfile.php', $options['context']->id, 'coursecat', 'description', null);
            return format_text($text, $cat->descriptionformat, $options);
        }
        return null;
    }

    /**
     * Gets the child categories of a given courses category
     *
     * The objects in the return array have proper set attributes
     * DEPTH, EXPANDSUBCATEGORIESDEPTH
     *
     * @return array of course_category instances
     */
    function get_child_categories() {
        $categoriesbyparent = self::get_categories(true);
        if (!($this->get_category() === null) &&
                ($categoriesbyparent = self::get_categories(true)) &&
                !empty($categoriesbyparent[$this->id])) {
            $childcategories = array();
            $cnt = 0;
            foreach ($categoriesbyparent[$this->id] as $categoryrec) {
                $attr = $this->attributes + array();
                $attr[self::ID] = $categoryrec->id;
                $attr[self::DEPTH] = $this->get_attr(self::DEPTH) + 1;
                $childcategories[$categoryrec->id] = new course_category($attr);
            }
            return $childcategories;
        } else {
            return array();
        }
    }

    /**
     * Returns the count of the child categories
     *
     * @return int
     */
    function get_child_categories_count() {
        if (!($this->get_category() === null) &&
                ($categoriesbyparent = self::get_categories(true)) &&
                !empty($categoriesbyparent[$this->id])) {
            return count($categoriesbyparent[$this->id]);
        }
        return 0;
    }

    protected $childcourses = null;
    protected function get_child_courses_int() {
        global $DB;
        if ($this->childcourses !== null) {
            return $this->childcourses;
        }

        $this->childcourses = array();
        // check if this category is hidden
        if ($this->get_category() === null) {
            return $this->childcourses;
        }

        // TODO this queries only direct children!
        list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
        $sql = "SELECT
                c.id,c.sortorder,c.visible,c.fullname,c.shortname,c.summary,c.category
                $ccselect
                FROM {course} c
                $ccjoin
                WHERE c.category = :categoryid ORDER BY c.sortorder ASC";
        $params = array('categoryid' => $this->id);
        if ($courses = $DB->get_records_sql($sql, $params)) {
            // loop throught them
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                context_instance_preload($course);
                if (!empty($course->visible) || has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
                    $this->childcourses[$course->id] = $course;
                }
            }
        }
        return $this->childcourses;
    }

    /**
     * Returns array of courses in this category
     *
     * @return array of rows from DB {courses} table
     */
    public function get_child_courses() {
        $childcourses = $this->get_child_courses_int();
        return $childcourses;
    }

    /**
     * Returns the number of visible courses in this category
     *
     * @return int
     */
    public function get_child_courses_count() {
        $childcourses = $this->get_child_courses_int();
        return count($childcourses);
    }

    /**
     * This function generates a structured array of courses and categories.
     *
     * The depth of categories is limited by $CFG->maxcategorydepth however there
     * is no limit on the number of courses!
     *
     * Suitable for use with the course renderers course_category_tree method:
     * $renderer = $PAGE->get_renderer('core','course');
     * echo $renderer->course_category_tree(get_course_category_tree());
     *
     * @global moodle_database $DB
     * @param int $id
     * @param int $depth
     */
    /*
    function get_course_category_tree($id = 0, $depth = 0) {
        global $DB, $CFG;
        $viewhiddencats = has_capability('moodle/category:viewhiddencategories', context_system::instance());
        $categories = get_child_categories($id);
        $categoryids = array();
        foreach ($categories as $key => &$category) {
            if (!$category->visible && !$viewhiddencats) {
                unset($categories[$key]);
                continue;
            }
            $categoryids[$category->id] = $category;
            if (empty($CFG->maxcategorydepth) || $depth <= $CFG->maxcategorydepth) {
                list($category->categories, $subcategories) = get_course_category_tree($category->id, $depth+1);
                foreach ($subcategories as $subid=>$subcat) {
                    $categoryids[$subid] = $subcat;
                }
                $category->courses = array();
            }
        }

        if ($depth > 0) {
            // This is a recursive call so return the required array
            return array($categories, $categoryids);
        }

        if (empty($categoryids)) {
            // No categories available (probably all hidden).
            return array();
        }

        // The depth is 0 this function has just been called so we can finish it off

        list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
        list($catsql, $catparams) = $DB->get_in_or_equal(array_keys($categoryids));
        $sql = "SELECT
                c.id,c.sortorder,c.visible,c.fullname,c.shortname,c.summary,c.category
                $ccselect
                FROM {course} c
                $ccjoin
                WHERE c.category $catsql ORDER BY c.sortorder ASC";
        if ($courses = $DB->get_records_sql($sql, $catparams)) {
            // loop throught them
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                context_instance_preload($course);
                if (!empty($course->visible) || has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
                    $categoryids[$course->category]->courses[$course->id] = $course;
                }
            }
        }
        return $categories;
    }
    */
}
