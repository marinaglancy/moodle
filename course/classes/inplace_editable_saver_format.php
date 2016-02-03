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
 * Contains class \core_course\inplace_editable_saver
 *
 * @package    core_course
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course;

use html_writer;
use context_course;
use lang_string;
use moodle_exception;
use coding_exception;

/**
 * Class allowing to quick edit a section name inline
 *
 * Must be overridden inside individual course formats
 *
 * @package    core_course
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class inplace_editable_saver_format {

    /** @var string */
    protected $component = null;

    /** @var string */
    protected $edithint = null;

    /** @var string */
    protected $editlabel = null;

    /**
     * Prepares the object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @return \core\output\inplace_editable
     */
    public function render_section_name($section, $linkifneeded = true, $editable = null) {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/course/lib.php');

        if ($editable === null) {
            $editable = !empty($USER->editing) && has_capability('moodle/course:update',
                context_course::instance($section->course));
        }

        $displayvalue = $title = get_section_name($section->course, $section);
        if ($linkifneeded) {
            // We use negative identifier if the link has to be suppressed.
            $url = course_get_url($section->course, $section->section, array('navigation' => true));
            if ($url) {
                $displayvalue = html_writer::link($url, $title);
            }
            $itemtype = 'sectionname';
        } else {
            $itemtype = 'sectionnamenl';
        }
        if (empty($this->edithint)) {
            $this->edithint = new lang_string('editsectionname');
        }
        if (empty($this->editlabel)) {
            $this->editlabel = new lang_string('newsectionname', '', $title);
        }

        return new \core\output\inplace_editable($this->component, $itemtype, $section->id, $editable,
            $displayvalue, $section->name, $this->edithint, $this->editlabel);
    }

    /**
     * Updates the value in the database and modifies this object respectively.
     *
     * ALWAYS check user permissions before performing an update! Throw exceptions if permissions are not sufficient
     * or value is not legit.
     *
     * @param string $itemtype
     * @param int $itemid
     * @param mixed $newvalue
     * @return \core\output\inplace_editable
     */
    public function update_value($itemtype, $itemid, $newvalue) {
        global $DB;
        if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
            $section = $DB->get_record('course_sections', array('id' => $itemid), '*', MUST_EXIST);
            require_login($section->course, false, null, true, true);
            $context = context_course::instance($section->course);
            require_capability('moodle/course:update', $context);

            $newtitle = clean_param($newvalue, PARAM_TEXT);
            if (strval($section->name) !== strval($newtitle)) {
                course_update_section($section->course, $section, array('name' => $newtitle));
            }
            return $this->render_section_name($section, ($itemtype === 'sectionname'), true);
        }
        throw new coding_exception('Unrecognised itemtype');
    }
}