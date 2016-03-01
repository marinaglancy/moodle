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
 * Contains class core\hook\pre_course_category_delete
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use coursecat;

/**
 * Hook executed before the course category is deleted
 *
 * Plugins can implement callbacks for this hook if they want to backup data or send notifications or similar.
 *
 * Do not delete any data in the callbacks for this hook - other callbacks may expect all data be still present.
 * If plugin-specific coursecat-related data need to be deleted the plugin should do it in the observer to the event
 * \core\event\course_category_deleted
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pre_course_category_delete extends base {

    /**
     * @var coursecat course category object. Use get_coursecat() or get_coursecat_id() to retrieve
     */
    protected $coursecat;

    /**
     * Method to create an instance of the hook
     *
     * @param coursecat $coursecat course category object
     * @return self
     */
    public static function create(coursecat $coursecat) {
        $hook = new static();
        $hook->coursecat = $coursecat;
        return $hook;
    }

    /**
     * Returns the course category that is about to be deleted
     * @return coursecat
     */
    public function get_coursecat() {
        return $this->coursecat;
    }

    /**
     * Returns the id of the course category that is about to be deleted
     * @return int
     */
    public function get_coursecat_id() {
        return $this->coursecat->id;
    }
}
