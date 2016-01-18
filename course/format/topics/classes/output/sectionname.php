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
 * Contains class \format_topics\output\sectionname
 *
 * @package    format_topics
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_topics\output;

use moodle_exception;

/**
 * Class allowing to quick edit a section name inline
 *
 * @package    format_topics
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sectionname extends \core_course\output\sectionname {

    /**
     * Constructor.
     *
     * @param int|stdClass $identifier section id or section record from database.
     * @param bool $suppresslink never display link to the section even if each section has a separate page
     */
    public function __construct($identifier, $suppresslink = false) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        parent::__construct($identifier, $suppresslink);
        if (course_get_format($this->section->course)->get_format() !== 'topics') {
            throw new moodle_exception('invalidcourseformat', 'error');
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        $title = get_section_name($this->section->course, $this->section);
        $this->edithint = get_string('editsectionname', 'format_topics');
        $this->editlabel = get_string('newsectionname', 'format_topics', $title);
        return parent::export_for_template($output);
    }
}