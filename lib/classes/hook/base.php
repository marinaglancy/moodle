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
 * Base hook class.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * All other hook classes must extend this class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var bool $executing Is the hook being executed? */
    protected static $executing = false;

    /**
     * Execute the callbacks.
     *
     * @param string $componentname when specified the hook is executed only for specific component or plugin
     * @param bool $throwexceptions if set to false (default) all exceptions during callbacks executions will be
     *      converted to debugging messages and will not prevent further execution of other callbacks
     * @return self
     */
    public function execute($componentname = null, $throwexceptions = false) {
        if (static::$executing) {
            // Prevent recursion.
            debugging('hook is already being executed', DEBUG_DEVELOPER);
            return $this;
        }
        static::$executing = true;
        try {
            manager::execute($this, $componentname, $throwexceptions);
        } catch (\Exception $e) {
            static::$executing = false;
            throw $e;
        }
        static::$executing = false;
        return $this;
    }

    /**
     * Returns the component (core component or full plugin name) that defines this hook.
     */
    public final function get_component() {
        $classname = get_called_class();
        $parts = explode('\\', $classname);
        if (count($parts) > 1) {
            return $parts[0];
        } else {
            $parts = explode('_', $classname);
            if (count($parts) > 2) {
                return $parts[0] . '_' . $parts[1];
            }
        }
        return null;
    }
}
