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
 * Fixtures for hook testing.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tests\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Test hook class
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unittest_executed extends \core\hook\base {

    /** @var  stdClass hook argument */
    protected $object;

    /**
     * Creates an instance of the hook
     *
     * @param \stdClass $object
     * @return static
     */
    public static function create($object) {
        $hook = new static();
        $hook->object = $object;
        return $hook;
    }

    /**
     * Returns a copy of the object (to prevent modification by callbacks).
     *
     * @return object
     */
    public function get_object() {
        return (object)(array)$this->object;
    }
}

/**
 * Test hook callbacks
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unittest_callback {

    /** @var array stores information about hooks */
    public static $info = array();

    /** @var array stores all hooks observed since last reset */
    public static $hook = array();

    /**
     * Resets caches
     */
    public static function reset() {
        self::$info = array();
        self::$hook = array();
    }

    /**
     * First callback
     * @param unittest_executed $hook
     */
    public static function observe_one(unittest_executed $hook) {
        self::$info[] = 'observe_one-'.$hook->get_object()->id;
        self::$hook[] = $hook;
    }

    /**
     * Second callback
     * @param unittest_executed $hook
     */
    public static function observe_two(unittest_executed $hook) {
        self::$info[] = 'observe_two-'.$hook->get_object()->id;
        self::$hook[] = $hook;
    }

    /**
     * Callback that throws an exception
     * @param unittest_executed $hook
     */
    public static function broken_callback(unittest_executed $hook) {
        self::$info[] = 'broken_callback-'.$hook->get_object()->id;
        self::$hook[] = $hook;
        throw new \Exception('someerror');
    }

    /**
     * Callback that tries to recursively execute hook
     * @param unittest_executed $hook
     */
    public static function recursive_callback1(unittest_executed $hook) {
        self::$info[] = 'recursive_callback1-'.$hook->get_object()->id;
        self::$hook[] = $hook;
        $hook->execute();
    }

    /**
     * Callback that tries to recursively execute hook
     * @param unittest_executed $hook
     */
    public static function recursive_callback2(unittest_executed $hook) {
        self::$info[] = 'recursive_callback2-'.$hook->get_object()->id;
        self::$hook[] = $hook;
        unittest_executed::create((object)array('id' => 3))->execute();
    }

    /**
     * Generic callback that can be used in unittests for any hook
     * @param \core\hook\base $hook
     */
    public static function generic_callback(\core\hook\base $hook) {
        self::$info[] = 'generic_callback';
        self::$hook[] = $hook;
    }
}
