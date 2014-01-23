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
 * Base class to represent an event in course recent activity.
 *
 * @package    core_course
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class to deal with recent activity inside the course.
 *
 * An instance of this class represents one event of activity inside the course,
 * for example: post in the forum, submission of assignment, chat session, etc.
 *
 * Non-static methods inside this class are reponsible for displaying information
 * about one event of recent activity.
 *
 * Static methods in this class are used to retrieve list of such events
 * when given time range and possibly other filters (particular module,
 * user, group). They also have to make sure that all items can be displayed
 * to the current user (check capabilities, group membership, module-specific
 * settings, etc.)
 *
 * Lists of events are displayed in "Recent activity block" and "Recent activity
 * report".
 *
 * Each module must extend this class and overwrite methods to implement
 * retrieval of the lists of recent activity events and methods to display them
 * in block and/or report.
 *
 * If module fails to extend the class, the legacy callbacks will be used:
 * XXX_print_recent_activity()
 * XXX_get_recent_mod_activity()
 * XXX_print_recent_mod_activity()
 *
 * Fallback to legacy callbacks will be removed in Moodle 2.9
 *
 * HOW TO MIGRATE A MODULE TO THE NEW RECENT ACTIVITY API:
 *
 * 1. Create a file mod/XXX/classes/recentactivity.php
 * 2. Inside the file define:
 *        namespace mod_XXX;
 *        class recentactivity extends \core_course\recentactivity {
 *            public static function get_recentactivity_types(...) {}
 *            public static function get_recentactivity(...) {}
 *            public function display(...) {}
 *            public function display_in_block(...) {}
 *        }
 * 3. Overwrite function get_recentactivity_types() (see examples in existing modules)
 * 4. Move code from the function XXX_get_recent_mod_activity() to
 *    \mod_XXX\recentactivity::get_recentactivity(). Note the difference
 *    in arguments and return value.
 * 5. Change the function get_recentactivity() so it can also work without
 *    specifying any filters including $filters['cm']. This will be used
 *    instead of XXX_print_recent_activity() to get data to display in a block.
 * 6. Move code from the function XXX_print_recent_mod_activity() to
 *    \mod_XXX\recentactivity::display().
 * 7. Move code responsible for displaying one event of recent activity in a block
 *    from XXX_print_recent_activity() to \mod_XXX\recentactivity::display_in_block().
 * 8. Remove legacy functions from mod/XXX/lib.php
 *
 * @package    core_course
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recentactivity {
    var $type;
    /** @var stdClass course where the recent activity event occured */
    var $course;
    /** @var int time when the recent activity event occured */
    var $timestamp;
    /** @var stdClass class representing user, may be null. */
    var $user;
    /** @var stdClass additional information about the recent activity event */
    var $content;
    /** @var stdClass data in the legacy format to pass to xxx_print_mod_recent_activity() function */
    var $legacydata;

    /**
     * Describes the types of recent activities the plugin can return.
     * Usually there is only one type of recent activities per plugin,
     * for example "New forum posts", "New assignment submissions".
     *
     * To be overwritten in the plugin.
     *
     * @param bool $forblock
     * @return array Associative array where key is the type of recent activity
     *    (must start with plugin name) and the value is the language string that
     *    will be used in the heading. Please do not use get_string() but rather
     *    new lang_string() here since this function may be only called to check
     *    if module supports recent activity interface
     */
    public static function get_recentactivity_types($forblock = false) {
        return array();
    }

    /**
     * Returns the list of recent activity types supported by the plugin.
     * Empty list means that plugin does not show anything for the recent activity
     * block or report.
     *
     * @param string $pluginname
     * @param bool $forblock
     * @return array Associative array where key is the type of recent activity
     *    and the value is the language string that will be used in the heading.
     */
    public static final function get_plugin_recentactivity_types($pluginname, $forblock = false) {
        $classname = '\\'.$pluginname.'\\recentactivity';
        if (class_exists($classname)) {
            return $classname::get_recentactivity_types($forblock);
        } else if (preg_match('/^mod_(.*)$/', $pluginname, $matches)) {
            // Legacy implementation, checking if function xxx_get_recent_mod_activity() exists.
            if (!$forblock && component_callback_exists($pluginname, 'get_recent_mod_activity')) {
                return array($matches[1] => new \lang_string('pluginname', $matches[1]));
            }
            if ($forblock && component_callback_exists($pluginname, 'print_recent_activity')) {
                return array($matches[1] => new \lang_string('pluginname', $matches[1]));
            }
        }
        return array();
    }

    /**
     * Returns the list of activity items that have recently happened inside particular course module.
     *
     * To be overwritten by plugin
     *
     * @param stdClass $course
     * @param int $timestart
     * @param array $filters
     * @param bool $forblock specifies whether result will be used in a block or in the report.
     * @return recentactivity[] array of instances of this class
     */
    public static function get_recentactivity($course, $timestart, $filters = array(), $forblock = false) {
        // Default implementation is not to support recent activity at all.
        return array();
    }

    /**
     * Returns the list of activity items that have recently happened inside the course in the specified plugin.
     *
     * @param int $timemodified
     * @param stdClass $course
     * @param string $pluginname
     * @param array $filters may include the following filters:
     *    cm - course module (instance of cm_info),
     *    userid - id of the user who performs the action,
     *    groupid - id of the group where action is performed
     * @param bool $forblock
     * @return recentactivity[] array of instances of this class
     */
    public static final function get_plugin_recentactivity($pluginname, $course, $timemodified, $filters = array(), $forblock = false) {
        $classname = '\\'.$pluginname.'\\recentactivity';
        if (class_exists($classname)) {
            return $classname::get_recentactivity($course, $timemodified, $filters, $forblock);
        } else if ($recentactivitytypes = self::get_plugin_recentactivity_types($pluginname, $forblock)) {
            // Fallback to legacy function (if possible).
            // TODO: debugging()
            $filters += array('userid' => '', 'groupid' => '');
            if (!empty($filters['cm'])) {
                return self::get_cm_recentactivity_legacy($timemodified, $filters['cm'], $filters['userid'], $filters['groupid']);
            } else if (preg_match('/^mod_(.*)$/', $pluginname, $matches)) {
                $instances = get_fast_modinfo($course)->get_instances_of($matches[1]);
                $activities = array();
                foreach ($instances as $cm) {
                    if ($cm->uservisible) {
                        $activities += self::get_cm_recentactivity_legacy($timemodified, $cm, $filters['userid'], $filters['groupid']);
                    }
                }
                return $activities;
            }
        }
        return array();
    }

    /**
     * Displays one event in recent activity in the report.
     * This is the legacy implementation calling xxx_print_recent_mod_activity()
     * Plugins must overwrite it to implement their own logic.
     *
     * @param bool $detail whether to display activity name
     */
    public function display($detail = true) {
        if (isset($this->legacydata)) {
            $print_recent_mod_activity = $this->cm->modname.'_print_recent_mod_activity';
            if (function_exists($print_recent_mod_activity)) {
                $coursecontext = \context_course::instance($this->cm->course);
                $print_recent_mod_activity($this->legacydata, $this->cm->course, $detail,
                        get_module_types_names(), has_capability('moodle/site:viewfullnames', $coursecontext));
            }
        }
    }

    protected function display_in_block_header() {
        static $strftimerecent = null;
        $output = '';
        $output .= '<div class="head">';
        $output .= '<div class="date">'.userdate($this->timestamp, $strftimerecent).'</div>';
        if (!empty($this->user)) {
            $context = \context_course::instance($this->cm->course);
            $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
            if (is_null($strftimerecent)) {
                $strftimerecent = get_string('strftimerecent');
            }
            $output .= '<div class="name">'.fullname($this->user, $viewfullnames).'</div>';
        }
        $output .= '</div>';
        return $output;

    }

    public function display_in_block() {
        return $this->display_in_block_header() . '<div class="info"></div>';
    }

    /**
     * Important, this function is used only for transition period (Moodle 2.7 and 2.8)
     * to maintain compatibility with an old API.
     */
    public static final function get_plugin_content_for_block($pluginname, $course, $timestart) {
        global $OUTPUT;
        $classname = '\\'.$pluginname.'\\recentactivity';
        $content = '';
        if (class_exists($classname)) {
            $activities = $classname::get_recentactivity($course, $timestart, array(), true);
            if (!empty($activities)) {
                $activitiesbytype = array();
                $types = $classname::get_recentactivity_types(true);
                foreach ($activities as $activity) {
                    if (!isset($activitiesbytype[$activity->type])) {
                        $activitiesbytype[$activity->type] = array();
                    }
                    $activitiesbytype[$activity->type][] = $activity;
                }
                foreach ($activitiesbytype as $type => $activities) {
                    $content .=  $OUTPUT->heading($types[$type].':', 3);
                    foreach ($activities as $activity) {
                        $content .= $activity->display_in_block();
                    }
                }
            }
        } else {
            // Legacy implementation, calling function xxx_print_recent_activity().
            $context = \context_course::instance($course->id);
            $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
            ob_start();
            $hascontent = component_callback($pluginname, 'print_recent_activity',
                    array($course, $viewfullnames, $timestart), false);
            if ($hascontent) {
                // TODO debugging ?
                $content = ob_get_contents();
            }
            ob_end_clean();
        }
        return $content;
    }

    /**
     * Legacy implementation, calls xxx_get_recent_mod_activity() and converts
     * the returned elements to instances of this class.
     *
     * @param int $timemodified
     * @param cm_info $cm
     * @param int $userid
     * @param int $groupid
     * @return array
     */
    private static function get_cm_recentactivity_legacy($timemodified, $cm, $userid="", $groupid="") {
        $get_recent_mod_activity = $cm->modname."_get_recent_mod_activity";

        $legacyactivities = array();
        $activities = array();
        $index = 0;
        $get_recent_mod_activity($legacyactivities, $index, $timemodified, $cm->course, $cm->id, $userid, $groupid);
        if (!empty($legacyactivities)) {
            foreach ($legacyactivities as $legacyactivity) {
                $activity = new static();
                $activity->timestamp = $legacyactivity->timestamp;
                $activity->legacydata = $legacyactivity;
                $activities[] = $activity;
            }
        }
        return $activities;
    }
}
