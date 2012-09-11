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
 *
 * @package    core_course
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Returns an instance of format class (extending format_base) for given course
 *
 * @param int|stdClass $courseorid either course id or
 *     an object that has the property 'format' and may contain property 'id'
 * @return format_base
 */
function course_get_format($courseorid) {
    return format_base::instance($courseorid);
}

// TODO reset caches in format_base::instance and format_xxx::format_instance
// TODO reset $this->course on rebuild_course_cache()
// TODO have the cache limit


/**
 * Base class for course formats
 *
 * Each course format must declare class
 * class format_FORMATNAME extends format_base {}
 * in file lib.php
 *
 * For each course just one instance of this class is created and it will always be returned by
 * course_get_format($courseorid). Format may store it's specific course-dependent options in
 * variables of this class.
 *
 * In rare cases instance of child class may be created just for format without course id
 * i.e. to check if format supports AJAX.
 *
 * Also course formats may extend class section_info and overwrite
 * format_base::build_section_cache() to return more information about sections.
 *
 * If you are upgrading from Moodle 2.3 start with copying the class format_legacy and renaming
 * it to format_FORMATNAME, then move the code from your callback functions into
 * appropriate functions of the class.
 *
 * @package    core_course
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class format_base {
    protected $courseid;
    protected $format;
    protected $course = false;

    /**
     * Creates a new instance of class
     *
     * Please use {@link course_get_format($courseorid)} to get an instance of the format class
     *
     * @param string $format
     * @param int $courseid
     * @return format_base
     */
    protected function __construct($format, $courseid) {
        $this->format = $format;
        $this->courseid = $courseid;
    }

    /**
     * Get class name for the format
     *
     * If course format xxx does not declare class format_xxx, format_legacy will be returned.
     * This function also includes lib.php file from corresponding format plugin
     *
     * @param string $format
     * @return string
     */
    protected static final function get_class_name($format) {
        global $CFG;
        static $classnames = array('site' => 'format_site');
        if (!isset($classnames[$format])) {
            $plugins = get_plugin_list('format'); // TODO filter only enabled
            if (!isset($plugins[$format])) {
                $defaultformat = reset($plugins); // TODO get default format
                debugging('Format plugin format_'.$format.' is not found or is not enabled. Using default format_'.$defaultformat);
                $usedformat = $defaultformat;
            } else {
                $usedformat = $format;
            }
            if (file_exists($plugins[$usedformat].'/lib.php')) {
                require_once $plugins[$usedformat].'/lib.php';
            }
            $classnames[$format] = 'format_'. $usedformat;
            if (!class_exists($classnames[$format])) {
                $classnames[$format] = 'format_legacy';
            }
        }
        return $classnames[$format];
    }

    /**
     * Returns an instance of the class
     *
     * @param int|stdClass $courseorid either course id or
     *     an object that has the property 'format' and may contain property 'id'
     * @return format_base
     */
    public static final function instance($courseorid) {
        global $DB;
        static $initialisedcourses = array();        
        if (!is_object($courseorid)) {
            $courseid = (int)$courseorid;
            if (isset($initialisedcourses[$courseid])) {
                $format = $initialisedcourses[$courseid];
            } else {
                $format = $DB->get_field('course', 'format', array('id' => $courseid));
            }
        } else {
            $format = $courseorid->format;
            if (isset($courseorid->id)) {
                $courseid = (int)$courseorid->id;
            } else {
                $courseid = 0;
            }
        }
        if ($courseid) {
            $initialisedcourses[$courseid] = $format;
        }
        $classname = self::get_class_name($format);
        return $classname::format_instance($format, $courseid);
    }

    /**
     * Returns an instance of the class for particular format and course id
     *
     * This function maybe overwritten if needed
     *
     * @return format_base
     */
    protected static function format_instance($format, $courseid) {
        $classname = self::get_class_name($format);
        static $instances = array();
        $courseid = (int)$courseid;
        if (!isset($instances[$courseid])) {
            $instances[$courseid] = new $classname($format, $courseid);
        }
        return $instances[$courseid];
    }

    /**
     * Returns the format name used by this course
     *
     * @return string
     */
    public final function get_format() {        
        return $this->format;
    }

    /**
     * Returns id of the course (0 if course is not specified)
     *
     * @return int
     */
    public final function get_courseid() {        
        return $this->courseid;
    }

    /**
     * Returns a record from course database table plus additional fields
     * that course format defines
     *
     * @return stdClass
     */
    public function get_course() {
        global $DB;
        if (!$this->courseid) {
            return null;
        }
        if ($this->course === false) {
            $this->course = $DB->get_record('course', array('id' => $this->courseid));
        }
        return $this->course;
    }

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return false;
    }

    /**
     * Returns a list of sections used in the course
     *
     * This is a shortcut to get_fast_modinfo()->get_section_info_all()
     * @see get_fast_modinfo()
     * @see course_modinfo::get_section_info_all()
     *
     * @return array of section_info objects
     */
    public final function get_sections() {
        if ($course = $this->get_course()) {
            $modinfo = get_fast_modinfo($course);
            return $modinfo->get_section_info_all();
        }
        return array();
    }

    /**
     * Builds a list of information about sections on a course to be stored in
     * the course cache. (Does not include information that is already cached
     * in some other way.)
     *
     * Used by {@link rebuild_course_cache()} function; do not use otherwise
     *
     * Course format may choose to cache more information about section and even
     * overwrite section_info class to store more section properties. It is very
     * recommended not to cache unnecessary information. Cached sections are
     * converted back to array of section_info objects in
     * {@link format_base::restore_sections_from_cache()}
     *
     * @return array Information about sections, indexed by section number (not id)
     */
    public function build_section_cache() {
        global $DB;
        if (!$this->courseid) {
            return array();
        }

        // Get section data
        $sections = $DB->get_records('course_sections', array('course' => $this->courseid), 'section',
                'section, id, course, name, summary, summaryformat, sequence, visible, ' .
                'availablefrom, availableuntil, showavailability, groupingid');
        $compressedsections = array();

        // Remove unnecessary data and add availability
        foreach ($sections as $number => $section) {
            // Clone just in case it is reused elsewhere (get_all_sections cache)
            $compressedsections[$number] = clone($section);
            section_info::convert_for_section_cache($compressedsections[$number]);
        }

        return $compressedsections;
    }

    /**
     * Used to convert information in course section cache into array of section_info objects
     *
     * This function is called from {@link course_modinfo::__construct} which is created by
     * {@link get_fash_modinfo()}
     *
     * It is called after the modules are retrieved from cache and the $modinfo->sections
     * is already populated with the list of modules ids inside each section.
     *
     * The retrieved sections are available by calling
     * course_get_format($courseorid)->get_sections()
     * or
     * get_fast_modinfo()->get_section_info_all()
     *
     * @param course_modinfo $modinfo Instance of course_modinfo calling this function
     * @param array $sectioncache value stored in section cache
     * @param int $userid user for whom the course_modinfo is created
     * @return array Array of section_info objects
     */
    public function restore_sections_from_cache($modinfo, $sectioncache, $userid) {
        $sectioninfo = array();
        foreach ($sectioncache as $number => $data) {
            // Calculate sequence
            if (isset($modinfo->sections[$number])) {
                $sequence = implode(',', $modinfo->sections[$number]);
            } else {
                $sequence = '';
            }
            // Expand
            $sectioninfo[$number] = new section_info($data, $number, $this->courseid,
                    $sequence, $modinfo, $userid);
        }
        return $sectioninfo;
    }

    /**
     * Returns information about section used in course
     *
     * @param int|stdClass $section either section number (field course_section.section) or row from course_section table
     * @param int $strictness
     * @return section_info
     */
    public final function get_section($section, $strictness = IGNORE_MISSING) {
        if (is_object($section)) {
            $section = $section->section;
        }
        $sections = $this->get_sections();
        if (array_key_exists($section, $sections)) {
            return $sections[$section];
        }
        if ($strictness == MUST_EXIST) {
            throw new moodle_exception('sectionnotexist');
        }
        return null;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     * @return Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        return get_string('sectionname', 'format_'.$this->format) . ' ' . $section->section;
    }
    
    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     * The property (array)testedbrowsers can be used as a parameter for {@see ajaxenabled()}.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        // no support by default
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = false;
        $ajaxsupport->testedbrowsers = array();
        return $ajaxsupport;
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        return null;
    }

    /**
     * Returns url for viewing the section
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     * @param bool $fornavigation if true and section has no separate page, return null
     * @return null|moodle_url
     */
    public function get_section_view_url($section, $fornavigation = false) {
        $url = new moodle_url('/course/view.php', array('id' => $this->get_courseid()));
        
        if (is_object($section) && isset($section->section)) {
            $sectionnum = $section->section;
        } else {
            $sectionnum = $section;
        }
        if (($course = $this->get_course()) && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $url->param('section', $sectionnum);
        } else {
            if ($fornavigation) {
                // no link in navigation for section that does not have it's own page
                return null;
            }
            $url->set_anchor('section-'.$sectionnum);
        }
        return $url;
    }

    /**
     * Loads all of the courses section into the navigation
     *
     * By default the method {@link global_navigation::load_generic_course_sections()} is called
     *
     * @param global_navigation $navigation
     * @param navigation_node $coursenode The course node within the navigation
     * @return array Array of sections
     */
    public function extend_navigation(&$navigation, navigation_node $coursenode) {
        if ($course = $this->get_course()) {
            return $navigation->load_generic_course_sections($course, $coursenode, $this->format);
        }
        return array();
    }
}

/**
 *
 * @package    
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_site extends format_base {
    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return Display name that the course format prefers, e.g. "Topic 2"
     */
    function get_section_name($section) {
        return get_string('site');
    }    
}

/**
 *
 * @package    
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_legacy extends format_base {

    /**
     * Returns an instance of the class for particular format and course id
     *
     * @param stdClass $course must contain the property 'format' and may contain property 'id'
     * @return format_base
     */
    protected static function format_instance($format, $courseid) {
        static $instances = array();
        $classname = self::get_class_name($format);
        if (!isset($instances[$format][$courseid])) {
            if (!isset($instances[$format])) {
                $instances[$format] = array();
            }
            $instances[$format][$courseid] = new $classname($format, $courseid);
        }
        return $instances[$format][$courseid];
    }    

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    function uses_sections() {
        global $CFG;
        // Note that lib.php in course format folder is already included by now
        $featurefunction = 'callback_'.$this->format.'_uses_sections';
        if (function_exists($featurefunction)) {
            return $featurefunction();
        }
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * This function calls function callback_FORMATNAME_get_section_name() if
     * it exists
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    function get_section_name($section) {
        // Use course formatter callback if it exists
        $namingfunction = 'callback_'.$this->format.'_get_section_name';
        if (function_exists($namingfunction) && ($course = $this->get_course())) {
            return $namingfunction($course, $this->get_section($section));
        }

        // else, default behavior:
        return parent::get_section_name($section);        
    }

    /**
     * Returns url for viewing this section or 
     *
     * This function calls function callback_FORMATNAME_get_section_name() if
     * it exists
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param bool $fornavigation if true and section has no separate page, return null
     * @return null|moodle_url
     */
    public function get_section_url($section, $fornavigation = false) {
        // Use course formatter callback if it exists
        $featurefunction = 'callback_'.$this->format.'_get_section_url';
        if (function_exists($featurefunction) && ($course = $this->get_course())) {
            if (is_object($section)) {
                $sectionnum = $section->section;
            } else {
                $sectionnum = $section;
            }
            if ($sectionnum) {
                $url = $featurefunction($course, $sectionnum);
                if ($url || $fornavigation) {
                    return $url;
                }
            }
        }

        // else, default behavior:
        return parent::get_section_url($section, $fornavigation);        
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     * The property (array)testedbrowsers can be used as a parameter for {@see ajaxenabled()}.
     *
     * @return stdClass
     */
    function supports_ajax() {
        // set up default values
        $ajaxsupport = parent::supports_ajax();

        // get the information from the course format library
        $featurefunction = 'callback_'.$this->format.'_ajax_support';
        if (function_exists($featurefunction)) {
            $formatsupport = $featurefunction();
            if (isset($formatsupport->capable)) {
                $ajaxsupport->capable = $formatsupport->capable;
            }
            if (is_array($formatsupport->testedbrowsers)) {
                $ajaxsupport->testedbrowsers = $formatsupport->testedbrowsers;
            }
        }
        return $ajaxsupport;
    }

    /**
     * Loads all of the courses section into the navigation
     *
     * This function utilisies a callback that can be implemented within the course
     * formats lib.php file to customise the navigation that is generated at this
     * point for the course.
     *
     * By default (if not defined) the parent method is called
     *
     * @param global_navigation $navigation
     * @param navigation_node $coursenode The course node within the navigation
     * @return array Array of sections
     */
    public function extend_navigation(&$navigation, navigation_node $coursenode) {
        $displayfunc = 'callback_'.$this->format.'_display_content';
        if (function_exists($displayfunc) && !$displayfunc()) {
            return array();
        }
        $featurefunction = 'callback_'.$this->format.'_load_content';
        if (function_exists($featurefunction) && ($course = $this->get_course())) {
            return $featurefunction($navigation, $course, $coursenode);
        } else {
            return parent::extend_navigation($navigation, $coursenode);
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * @return array This will be passed in ajax respose
     */
    function ajax_section_move() {
        $featurefunction = 'callback_'.$this->format.'_ajax_section_move';
        if (function_exists($featurefunction) && ($course = $this->get_course())) {
            return $featurefunction($course);
        } else {
            return parent::ajax_section_move();
        }
    }
}