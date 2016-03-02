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
 * Contains class core\hook\inplace_editable
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
class inplace_editable extends base {

    /** @var string */
    protected $itemtype;

    /** @var int */
    protected $itemid;

    /** @var string */
    protected $value;

    /** @var \core\output\inplace_editable */
    protected $output = null;

    /**
     * Method to create an instance of the hook
     *
     * @param string $itemtype
     * @param int $itemid
     * @param string $value new value for the item
     * @return self
     */
    public static function create($itemtype, $itemid, $value) {
        $hook = new static();
        $hook->itemtype = $itemtype;
        $hook->itemid = $itemid;
        $hook->value = $value;
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
        if ($componentname === null) {
            throw new \coding_exception('This hook can only be executed for individual component');
        }
        parent::execute($componentname, $throwexceptions);
        if ($this->output === null) {
            // No callback found, look for legacy implementations.
            $tmpl = component_callback($componentname, 'inplace_editable',
                array($this->itemtype, $this->itemid, $this->value));
            if (!$tmpl || !($tmpl instanceof \core\output\inplace_editable)) {
                throw new \moodle_exception('inplaceeditableerror');
            }
            $this->set_output($tmpl);
        }
        return $this;
    }

    /**
     * Item type that was passed as argument to the hook
     * @return string
     */
    public function get_item_type() {
        return $this->itemtype;
    }

    /**
     * Item id that was passed as argument to the hook
     * @return int
     */
    public function get_item_id() {
        return $this->itemid;
    }

    /**
     * New value that was passed as argument to the hook
     * @param string $type expected format of param after cleaning, for example PARAM_INT, PARAM_NOTAGS, etc.
     * @return mixed
     */
    public function get_value($type) {
        return clean_param($this->value, $type);
    }

    /**
     * Used to set return value of the hook, there could be several callbacks in the same component but only
     * one can set the output
     * @param \core\output\inplace_editable $output
     */
    public function set_output(\core\output\inplace_editable $output) {
        if ($this->output !== null) {
            // TODO exception in dev mode?
            debugging('Attempt to set output more than once', DEBUG_DEVELOPER);
            return;
        }
        $this->output = $output;
    }

    /**
     * Used by the caller to retrieve the hook return value
     * @return \core\output\inplace_editable
     */
    public function get_output() {
        return $this->output;
    }
}
