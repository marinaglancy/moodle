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
 * Contains class \core_course\output\sectionname
 *
 * @package    core_course
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\output;

use html_writer;
use context_course;

/**
 * Class allowing to quick edit a section name inline
 *
 * Must be overridden inside individual course formats
 *
 * @package    core_course
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class sectionname extends \core\output\editabletitle {

    /** @var stdClass section object */
    protected $section;

    /**
     * Constructor.
     *
     * Overriding course formats must ensure that section belongs to the course with the proper format.
     *
     * @param int|stdClass $identifier section id or section record from database.
     * @param bool $suppresslink never display link to the section even if each section has a separate page
     */
    public function __construct($identifier, $suppresslink = false) {
        global $DB, $USER;
        if (is_object($identifier)) {
            $this->section = $identifier;
        } else {
            // We use negative identifier if the link has to be suppressed.
            $this->section = $DB->get_record('course_sections', array('id' => abs($identifier)), '*', MUST_EXIST);
            if ($identifier < 0) {
                $suppresslink = true;
            }
        }
        parent::__construct(($suppresslink ? -1 : 1) * $this->section->id);
        $this->editable = !empty($USER->editing) && has_capability('moodle/course:update',
                context_course::instance($this->section->course));
    }

    /**
     * Updates the value in the database and modifies this object respectively.
     *
     * @param string $newvalue new section name
     */
    public function update($newvalue) {
        global $DB;
        require_login($this->section->course, false, null, true, true);
        $context = context_course::instance($this->section->course);
        require_capability('moodle/course:update', $context);

        $newvalue = clean_param($newvalue, PARAM_TEXT);
        if (\core_text::strlen($newvalue) > 255) {
            throw new \moodle_exception('maximumchars', 'moodle', '', 255);
        }
        if (strval($this->section->name) !== strval($newvalue)) {
            $DB->update_record('course_sections', array('id' => $this->section->id, 'name' => $newvalue));
            $this->section->name = $newvalue;
            rebuild_course_cache($this->section->course, true);
            // Trigger an event for course section update.
            $event = \core\event\course_section_updated::create(
                array(
                    'objectid' => $this->section->id,
                    'courseid' => $this->section->course,
                    'context' => $context,
                    'other' => array('sectionnum' => $this->section->section)
                )
            );
            $event->trigger();
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $CFG;
        require_once($CFG->dirroot.'/course/lib.php');
        $this->displayvalue = $title = get_section_name($this->section->course, $this->section);
        if ($this->identifier > 0) {
            // We use negative identifier if the link has to be suppressed.
            $url = course_get_url($this->section->course, $this->section->section, array('navigation' => true));
            if ($url) {
                $this->displayvalue = html_writer::link($url, $title);
            }
        }
        $this->value = $this->section->name;
        if (empty($this->edithint)) {
            $this->edithint = get_string('editsectionname');
        }
        if (empty($this->editlabel)) {
            $this->editlabel = get_string('newsectionname', '', $title);
        }
        return parent::export_for_template($output);
    }

}