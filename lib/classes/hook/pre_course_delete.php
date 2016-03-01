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
 * Contains class core\hook\pre_course_delete
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

use core_component;
use stdClass;

/**
 * Hook executed before the course is deleted
 *
 * This hook is not executed when only course contents is deleted, for example when another course is restored
 * into this one with replacing contents.
 *
 * Plugins can implement callbacks for this hook if they want to backup data or send notifications or similar.
 *
 * Do not delete any data in the callbacks for this hook - other callbacks may expect all data be still present.
 * If plugin-specific course-related data need to be deleted the plugin should do it in the observer to the event
 * \core\event\course_deleted or \core\event\course_content_deleted
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pre_course_delete extends base {

    /**
     * @var array record from course table converted to array. Use get_course() or get_course_id() to retrieve
     */
    protected $course;

    /**
     * Method to create an instance of the hook
     *
     * @param stdClass $course record from course table
     * @return self
     */
    public static function create($course) {
        $hook = new static();
        $hook->course = (array)$course;
        return $hook;
    }

    /**
     * Returns the course that is about to be deleted
     * @return stdClass
     */
    public function get_course() {
        // Return the copy of the course so that callbacks can not modify the original.
        return (object)$this->course;
    }

    /**
     * Returns the id of the course that is about to be deleted
     * @return int
     */
    public function get_course_id() {
        return $this->course['id'];
    }
}
