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
 * Hook manager class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

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
     * @param string $componentname when specified the hook is executed only for specific component or plugin
     * @param bool $throwexceptions if set to false (default) all exceptions during callbacks executions will be
     *      converted to debugging messages and will not prevent further execution of other callbacks
     * @return base returns the hook instance to allow chaining
     */
    public static function execute(base $hook, $componentname = null, $throwexceptions = false) {
        global $CFG;

        if (during_initial_install()) {
            return $hook;
        }
        if ($CFG->debugdeveloper) {
            self::validate_hook($hook);
        }
        self::init_all_callbacks();

        $hookname = '\\' . get_class($hook);
        if (!isset(self::$allcallbacks[$hookname])) {
            return $hook;
        }

        if ($componentname !== null) {
            $componentname = \core_component::normalize_componentname($componentname);
        }

        foreach (self::$allcallbacks[$hookname] as $callback) {
            if ($componentname !== null && $callback->component !== $componentname) {
                continue;
            }
            if (isset($callback->includefile) and file_exists($callback->includefile)) {
                include_once($callback->includefile);
            }
            if (is_callable($callback->callable)) {
                if ($throwexceptions) {
                    call_user_func($callback->callable, $hook);
                } else {
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
        self::add_component_callbacks('core', $CFG->dirroot . '/lib');

        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $ignored) {
            $plugins = \core_component::get_plugin_list($plugintype);

            foreach ($plugins as $pluginname => $fulldir) {
                self::add_component_callbacks($plugintype . '_' . $pluginname, $fulldir);
            }
        }

        self::order_all_callbacks();

        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache->set('all', self::$allcallbacks);
            $cache->set('dirroot', $CFG->dirroot);
        }
    }

    /**
     * Read callbacks from hooks.php file in the component and add them.
     * @param string $componentname
     * @param string $fulldir
     */
    protected static function add_component_callbacks($componentname, $fulldir) {
        $file = "$fulldir/db/hooks.php";
        if (!file_exists($file)) {
            return;
        }
        $callbacks = null;
        include($file);

        if (!is_array($callbacks)) {
            return;
        }

        self::add_callbacks($callbacks, $file, $componentname);
    }

    /**
     * Add callbacks.
     * @param array $callbacks
     * @param string $file
     * @param string $componentname
     */
    protected static function add_callbacks(array $callbacks, $file, $componentname) {
        global $CFG;
        foreach ($callbacks as $callback) {
            if (empty($callback['hookname']) or !is_string($callback['hookname'])) {
                debugging("Invalid 'hookname' detected in $file callback definition", DEBUG_DEVELOPER);
                continue;
            }
            if (empty($callback['callback'])) {
                debugging("Invalid 'callback' detected in $file callback definition", DEBUG_DEVELOPER);
                continue;
            }
            $o = new \stdClass();
            $o->callable = $callback['callback'];
            if ($componentname === 'core' && !empty($callback['component'])) {
                $o->component = $callback['component'];
            } else {
                $o->component = $componentname;
            }
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
            self::$allcallbacks['\\' . ltrim($callback['hookname'], '\\')][] = $o;
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
     * Checks that hook classname is listed in the lib/db/hooks.php of the respective component (or core).
     * This function is only executed in the debugging mode.
     * @param \core\hook\base $hook
     */
    protected static function validate_hook(base $hook) {
        global $CFG;
        $hookname = get_class($hook);
        $component = $hook->get_component();
        if (PHPUNIT_TEST && $component === 'core_tests') {
            // Ignore hooks defined in phpunit fixtures.
            return;
        }
        list($type, $plugin) = \core_component::normalize_component($component);
        if ($type === 'core') {
            $file = $CFG->dirroot . '/lib/db/hooks.php';
        } else {
            $dir = \core_component::get_plugin_directory($type, $plugin);
            $file = $dir.'/lib/db/hooks.php';
            if (!$dir) {
                debugging("Could not determine component that defines hook [$component] \\" . $hookname
                        . ", make sure that class name starts with a full frankenstyle name of plugin"
                        . " or is located in an appropriate namespace.",
                        DEBUG_DEVELOPER);
                return;
            }
        }
        $hooks = null;
        if (file_exists($file)) {
            include($file);
        }
        if (is_array($hooks) && !in_array($hookname, $hooks) && !in_array('\\' . $hookname, $hooks)) {
            debugging('Component ' . $component . ' must list \\' . $hookname .
                    ' in the $hooks array in lib/db/hooks.php',
                    DEBUG_DEVELOPER);
        }
    }

    /**
     * Replace all standard callbacks.
     * @param array $callbacks
     * @return array
     *
     * @throws \coding_exception if used outside of unit tests.
     */
    public static function phpunit_replace_callbacks(array $callbacks) {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot override hook callbacks outside of phpunit tests!');
        }

        self::phpunit_reset();
        self::$allcallbacks = array();
        self::$reloadaftertest = true;

        self::add_callbacks($callbacks, 'phpunit', 'core_phpunit');
        self::order_all_callbacks();

        return self::$allcallbacks;
    }

    /**
     * Reset everything if necessary.
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
