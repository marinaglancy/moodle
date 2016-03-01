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
 * Contains class core\hook\pre_user_delete
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
 * Hook executed before the user is deleted
 *
 * Plugins can implement callbacks for this hook if they want to backup data or send notifications or similar.
 *
 * Do not delete any data in the callbacks for this hook - other callbacks may expect all data be still present.
 * If plugin-specific user-related data need to be deleted the plugin should do it in the observer to the event
 * \core\event\user_deleted
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pre_user_delete extends base {

    /**
     * @var array record from user table converted to array. Use get_user() or get_user_id() to retrieve
     */
    protected $user;

    /**
     * Method to create an instance of the hook
     *
     * @param stdClass $user record from user table
     * @return self
     */
    public static function create($user) {
        $hook = new static();
        $hook->user = (array)$user;
        return $hook;
    }

    /**
     * Returns the user that is about to be deleted
     * @return stdClass
     */
    public function get_user() {
        // Return the copy of the user so that callbacks can not modify the original.
        return (object)$this->user;
    }

    /**
     * Returns the id of the user that is about to be deleted
     * @return int
     */
    public function get_user_id() {
        return $this->user['id'];
    }
}
