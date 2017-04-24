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
 * Course module stdClass proxy.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\proxies;

defined('MOODLE_INTERNAL') || die();

/**
 * Course module stdClass proxy.
 *
 * This implementation differs from the regular std_proxy in that it takes
 * a module name and instance instead of an id to construct the proxied class.
 *
 * This is needed as the event table does not store the id of course modules
 * instead it stores the module name and instance.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_std_proxy extends std_proxy implements proxy_interface {

    /** @var array Whatever was specified in set() method. Proxied object can be an instance of cm_info
     * and setting values is not allowed there. */
    protected $overwrites = [];

    /**
     * module_std_proxy constructor.
     *
     * @param int $modulename The module name.
     * @param callable $instance The module instance.
     * @param \stdClass|int $courseorid course this module belongs to or 0 if unknown
     * @param \stdClass $base Class containing base values.
     */
    public function __construct($modulename, $instance, $courseorid, \stdClass $base = null) {
        $this->callbackargs = [$modulename, $instance, $courseorid];
        $this->callback = [$this, 'callback'];
        $this->base = $base ?: new \stdClass();
        $this->base->modname = $modulename;
        $this->base->instance = $instance;
        if (is_object($courseorid)) {
            $this->base->course = $courseorid->id;
        } else if ($courseorid) {
            $this->base->course = $courseorid;
        }
    }

    public function get_id() {
        return $this->get_proxied_instance()->id;
    }

    /**
     * Callback used as $this->callback
     *
     * @param string $modulename
     * @param int $instance
     * @param int|\stdClass $courseorid
     * @return \stdClass
     */
    protected function callback($modulename, $instance, $courseorid) {
        if ($courseorid) {
            $cm = get_fast_modinfo($courseorid)->instances[$modulename][$instance];
            return $cm;
        } else {
            return get_coursemodule_from_instance($modulename, $instance);
        }
    }

    public function get($member) {
        if ($member === 'id') {
            return $this->get_id();
        }

        if ($this->base && property_exists($this->base, $member)) {
            return $this->base->{$member};
        }

        if (array_key_exists($member, $this->overwrites)) {
            return $this->overwrites[$member];
        }

        $obj = $this->get_proxied_instance();
        if ($obj instanceof \cm_info) {
            return $obj->$member;
        }

        if (!property_exists($this->get_proxied_instance(), $member)) {
            throw new member_does_not_exist_exception(sprintf('Member %s does not exist', $member));
        }

        return $this->get_proxied_instance()->{$member};
    }

    public function set($member, $value) {
        $this->get($member); // This will trigger exception if $member is not a valid property.
        $this->overwrites[$member] = $value;
    }
}
