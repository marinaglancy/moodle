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
 * Contains class core\hook\scale_used
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
 * TODO
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scale_used extends base {

    /** @var bool */
    protected $scaleused = false;

    /** @var int */
    protected $scaleid;

    /**
     * Method to create an instance of the hook
     *
     * @param string $itemtype
     * @param int $itemid
     * @param string $value new value for the item
     * @return self
     */
    public static function create($scaleid) {
        $hook = new static();
        if (!$scaleid) {
            throw new \coding_exception('Scale id must be specified');
        }
        $hook->scaleid = $scaleid;
        return $hook;
    }

    /**
     * Execute the callbacks.
     *
     * @param string $componentname when specified the hook is executed only for specific component or plugin
     * @param bool $throwexceptions if set to false (default) all exceptions during callbacks executions will be
     *      converted to debugging messages and will not prevent further execution of other callbacks
     * @return self
     */
    public function execute($componentname = null, $throwexceptions = false) {
        parent::execute($componentname, $throwexceptions);
        if (!$this->scaleused && $this->execute_legacy()) {
            $this->set_is_used();
        }
        return $this;
    }

    /**
     * Check implementations of <modname>_scale_used_anywhere
     *
     * @return bool
     */
    protected function execute_legacy() {
        $pluginlist = get_plugin_list_with_function('mod', 'scale_used_anywhere');
        foreach ($pluginlist as $mod => $functionname) {
            if ($functionname($this->get_scale_id())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sets that scale is used
     */
    public function set_is_used() {
        $this->scaleused = true;
    }

    /**
     * Returns if scale is used anywhere
     * @return bool
     */
    public function get_is_used() {
        return $this->scaleused;
    }

    /**
     * Returns ID of the scale in question
     * @return int
     */
    public function get_scale_id() {
        return $this->scaleid;
    }
}
