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
 * Contains class core\hook\pre_block_instance_delete
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Hook executed before block instance is deleted
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pre_block_instance_delete extends base {

    /**
     * @var array record from block_instances table converted to array. Use get_block_instance() or
     *      get_block_instance_id() to retrieve
     */
    protected $blockinstance;

    /**
     * Method to create an instance of the hook
     *
     * @param stdClass $blockinstance a row from the block_instances table
     * @return self
     */
    public static function create($blockinstance) {
        $hook = new static();
        $hook->blockinstance = (array)$blockinstance;
        return $hook;
    }

    /**
     * Returns the block instance that is about to be deleted
     * @return stdClass
     */
    public function get_block_instance() {
        // Return the copy of the block instance object so that callbacks can not modify the original.
        return (object)$this->blockinstance;
    }

    /**
     * Returns the id of the block instance that is about to be deleted
     * @return int
     */
    public function get_block_instance_id() {
        return $this->blockinstance['id'];
    }
}
