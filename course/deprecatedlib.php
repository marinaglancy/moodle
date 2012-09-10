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
 * Functions deprecated in 2.4 with the introduction of course formats class {@link format_base}
 *
 * @package    core_course
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Returns an array of sections for the requested course id
 *
 * It is usually not recommended to display the list of sections used
 * in course because the course format may have it's own way to do it.
 *
 * If you need to just display the name of the section where the
 * module is used please call:
 * course_get_format($courseorid)->get_section_full_name($section)
 *
 * Since Moodle 2.3, it is more efficient to get this data by calling
 * $modinfo = get_fast_modinfo($course);
 * $sections = $modinfo->get_section_info_all()
 * {@link get_fast_modinfo()}
 * {@link course_modinfo::get_section_info_all()}
 *
 * Also you can use functions from course formats:
 * {@link format_base}
 * 
 * List of all sections:
 * course_get_format($courseorid)->get_sections()
 *
 * Information about one section (instance of section_info):
 * course_get_format($courseorid)->get_section($section)
 *
 * Just the name of the section
 * course_get_format($courseorid)->get_section_name($section)
 *
 * Full name of the section (i.e. with all parent sections):
 * course_get_format($courseorid)->get_section_full_name($section)
 *
 * @deprecated since 2.4
 *
 * @param int $courseid
 * @return array Array of section_info objects
 */
function get_all_sections($courseid) {
    debugging('get_all_sections() is deprecated. See phpdocs for this function', DEBUG_DEVELOPER);
    return course_get_format($courseid)->get_sections();
}

/**
 * Returns course section - creates new if does not exist yet.
 *
 * @deprecated since 2.4
 * @see format_base::get_or_create_section()
 *
 * @param int $section section number
 * @param int $courseid
 * @return object $course_section object
 */
function get_course_section($section, $courseid) {
    debugging('get_course_section() is deprecated. Please use course_get_format($courseid)->get_or_create_section($section)');
    return course_get_format($courseid)->get_or_create_section($section);
}

/**
 * Returns the display name of the given section that the course format prefers
 *
 * @deprecated since 2.4
 * @see format_base::get_section_name()
 * @see format_base::get_section_full_name()
 *
 * @param stdClass $course The course to get the section name for
 * @param stdClass $section Section object from database
 * @return Display name that the course format prefers, e.g. "Week 2"
 */
function get_section_name(stdClass $course, stdClass $section) {
    debugging('get_section_name() is deprecated. Please use '.
            'course_get_format($course)->get_section_name($section) or '.
            'course_get_format($course)->get_section_full_name($section)', DEBUG_DEVELOPER);
    return course_get_format($course)->get_section_name($section);
}

/**
 * Gets the generic section name for a courses section
 *
 * The global function is deprecated. Each course format can define their own generic section name
 *
 * @deprecated since 2.4
 * @see format_base::get_section_name()
 * @see format_base::get_or_create_section()
 *
 * @param string $format Course format ID e.g. 'weeks' $course->format
 * @param stdClass $section Section object from database
 * @return Display name that the course format prefers, e.g. "Week 2"
 */
function get_generic_section_name($format, stdClass $section) {
    debugging('get_generic_section_name() is deprecated. Please use appropriate functionality from class format_base', DEBUG_DEVELOPER);
    return get_string('sectionname', "format_$format") . ' ' . $section->section;
}

/**
 * Tells if current course format uses sections
 *
 * @deprecated since 2.4
 * @see format_base::uses_sections()
 *
 * @param string $format Course format ID e.g. 'weeks' $course->format
 * @return bool
 */
function course_format_uses_sections($format) {
    debugging('course_format_uses_sections() is deprecated. Please use course_get_format($course)->uses_sections()', DEBUG_DEVELOPER);
    $course = new stdClass();
    $course->format = $format;
    return course_get_format($course)->uses_sections();
}

/**
 * Returns the information about the ajax support in the given source format
 *
 * The returned object's property (boolean)capable indicates that
 * the course format supports Moodle course ajax features.
 * The property (array)testedbrowsers can be used as a parameter for {@see ajaxenabled()}.
 *
 * @deprecated since 2.4
 * @see format_base::supports_ajax()
 *
 * @param string $format
 * @return stdClass
 */
function course_format_ajax_support($format) {
    //debugging('course_format_ajax_support() is deprecated. Please use course_get_format($course)->supports_ajax()', DEBUG_DEVELOPER);
    $course = new stdClass();
    $course->format = $format;
    return course_get_format($course)->supports_ajax();
}
