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
 * Contains class \format_weeks\inplace_editable_saver
 *
 * @package    format_weeks
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_weeks;

use lang_string;

/**
 * Class allowing to quick edit a section name inline
 *
 * @package    format_weeks
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable_saver extends \core_course\inplace_editable_saver_format {

    /**
     * Prepares the object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @return \core\output\inplace_editable
     */
    public function render_section_name($section, $linkifneeded = true, $editable = null) {
        $title = get_section_name($section->course, $section);
        $this->component = 'format_weeks';
        $this->edithint = new lang_string('editsectionname', 'format_weeks');
        $this->editlabel = new lang_string('newsectionname', 'format_weeks', $title);
        return parent::render_section_name($section, $linkifneeded, $editable);
    }
}