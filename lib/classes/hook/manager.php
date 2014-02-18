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

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook manager class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Manager executing hook callbacks.
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class manager {
    /** @var array cache of all callbacks */
    protected static $allcallbacks = null;

    /** @var bool should we reload callbacks after the test? */
    protected static $reloadaftertest = false;

    /**
     * Execute all hook callbacks.
     *
     * @param base $hook
     * @return base returns the hook instance to allow chaining
     */
    public static function execute(base $hook) {
        global $CFG;

        if (during_initial_install()) {
            return $hook;
        }
        self::init_all_callbacks();

        $hookname = '\\' . get_class($hook);
        if (!isset(self::$allcallbacks[$hookname])) {
            return $hook;
        }

        foreach (self::$allcallbacks[$hookname] as $callback) {
            if (isset($callback->includefile) and file_exists($callback->includefile)) {
                include_once($callback->includefile);
            }
            if (is_callable($callback->callable)) {
                try {
                    call_user_func($callback->callable, $hook);
                } catch (\Exception $e) {
                    // Callbacks are executed before installation and upgrade, this may throw errors.
                    if (empty($CFG->upgraderunning)) {
                        // Ignore errors during upgrade, otherwise warn developers.
                        debugging("Exception encountered in hook callback '$callback->callable': " .
                            $e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
                    }
                }
            } else {
                debugging("Cannot execute hook callback '$callback->callable'");
            }
        }

        // Note: there is no protection against infinite recursion, sorry.

        return $hook;
    }

    /**
     * Initialise the list of callbacks.
     */
    protected static function init_all_callbacks() {
        global $CFG;

        if (is_array(self::$allcallbacks)) {
            return;
        }

        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache = \cache::make('core', 'hookcallbacks');
            $cached = $cache->get('all');
            $dirroot = $cache->get('dirroot');
            if ($dirroot === $CFG->dirroot and is_array($cached)) {
                self::$allcallbacks = $cached;
                return;
            }
        }

        self::$allcallbacks = array();

        $plugintypes = \core_component::get_plugin_types();
        $systemdone = false;
        foreach ($plugintypes as $plugintype => $ignored) {
            $plugins = \core_component::get_plugin_list($plugintype);
            if (!$systemdone) {
                $plugins[] = "$CFG->dirroot/lib";
                $systemdone = true;
            }

            foreach ($plugins as $fulldir) {
                if (!file_exists("$fulldir/db/hooks.php")) {
                    continue;
                }
                $callbacks = null;
                include("$fulldir/db/hooks.php");
                if (!is_array($callbacks)) {
                    continue;
                }
                self::add_callbacks($callbacks, "$fulldir/db/hooks.php");
            }
        }

        self::order_all_callbacks();

        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache->set('all', self::$allcallbacks);
            $cache->set('dirroot', $CFG->dirroot);
        }
    }

    /**
     * Add callbacks.
     * @param array $callbacks
     * @param string $file
     */
    protected static function add_callbacks(array $callbacks, $file) {
        global $CFG;

        foreach ($callbacks as $callback) {
            if (empty($callback['hookname']) or !is_string($callback['hookname'])) {
                debugging("Invalid 'hookname' detected in $file callback definition", DEBUG_DEVELOPER);
                continue;
            }
            if (strpos($callback['hookname'], '\\') !== 0) {
                $callback['hookname'] = '\\' . $callback['hookname'];
            }
            if (empty($callback['callback'])) {
                debugging("Invalid 'callback' detected in $file callback definition", DEBUG_DEVELOPER);
                continue;
            }
            $o = new \stdClass();
            $o->callable = $callback['callback'];
            if (!isset($callback['priority'])) {
                $o->priority = 0;
            } else {
                $o->priority = (int)$callback['priority'];
            }
            if (empty($callback['includefile'])) {
                $o->includefile = null;
            } else {
                if ($CFG->admin !== 'admin' and strpos($callback['includefile'], '/admin/') === 0) {
                    $callback['includefile'] = preg_replace('|^/admin/|', '/' . $CFG->admin . '/', $callback['includefile']);
                }
                $callback['includefile'] = $CFG->dirroot . '/' . ltrim($callback['includefile'], '/');
                if (!file_exists($callback['includefile'])) {
                    debugging("Invalid 'includefile' detected in $file callback definition", DEBUG_DEVELOPER);
                    continue;
                }
                $o->includefile = $callback['includefile'];
            }
            self::$allcallbacks[$callback['hookname']][] = $o;
        }
    }

    /**
     * Reorder callbacks to allow quick lookup of callback for each hook.
     */
    protected static function order_all_callbacks() {
        foreach (self::$allcallbacks as $classname => $callbacks) {
            \core_collator::asort_objects_by_property($callbacks, 'priority', \core_collator::SORT_NUMERIC);
            self::$allcallbacks[$classname] = array_reverse($callbacks);
        }
    }

    /**
     * Replace all standard callbacks.
     * @param array $callbacks
     * @return array
     *
     * @throws \coding_Exception if used outside of unit tests.
     */
    public static function phpunit_replace_callbacks(array $callbacks) {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot override hook callbacks outside of phpunit tests!');
        }

        self::phpunit_reset();
        self::$allcallbacks = array();
        self::$reloadaftertest = true;

        self::add_callbacks($callbacks, 'phpunit');
        self::order_all_callbacks();

        return self::$allcallbacks;
    }

    /**
     * Reset everything if necessary.
     * @private
     *
     * @throws \coding_Exception if used outside of unit tests.
     */
    public static function phpunit_reset() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot reset hook manager outside of phpunit tests!');
        }
        if (!self::$reloadaftertest) {
            self::$allcallbacks = null;
        }
        self::$reloadaftertest = false;
    }
}
