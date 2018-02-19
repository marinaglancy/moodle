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
 * Signup session helper.
 *
 * @package    core_auth
 * @copyright  2018 Mihail Geshoski (mihail@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_auth\session;

defined('MOODLE_INTERNAL') || die();

use \core_auth\session\auth_session_base;


/**
 * Signup session helper class.
 *
 * @copyright  2018 Mihail Geshoski (mihail@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signup extends auth_session_base {

    /**
     * Maximum time the session can be active.
     */
    const MAX_SESSION_TIME = 1800;

    /**
     * Create a signup session.
     */
    public static function create() {
        global $SESSION;

        if (!isset($SESSION->auth)) {
            $SESSION->auth = new \stdClass();
        }
        if (!isset($SESSION->auth->signup)) {
            $SESSION->auth->signup = new \stdClass();
        }

        $SESSION->auth->signup->timecreated = time();
    }

    /**
     * Check if a signup session is set.
     *
     * @return bool
     */
    public static function is_set() {
        global $SESSION;

        return isset($SESSION->auth->signup);
    }

    /**
     * Destroy the signup session.
     */
    public static function destroy() {
        global $SESSION;

        unset($SESSION->auth->signup);
    }

    /**
     * Set minor status.
     *
     * @param bool $isminor
     */
    public static function set_minor_status($isminor) {
        global $SESSION;

        $SESSION->auth->signup->is_minor = $isminor;
    }

    /**
     * Get the minor status from a signup session.
     *
     * @return bool
     */
    public static function get_minor_status() {
        global $SESSION;

        return $SESSION->auth->signup->is_minor;
    }

    /**
     * Get the creation time of a signup session.
     *
     * @return int
     */
    public static function get_timecreated() {
        global $SESSION;

        return $SESSION->auth->signup->timecreated;
    }

    /**
     * Check if a minor status is set.
     *
     * @return bool
     */
    public static function is_set_minor_status() {
        global $SESSION;

        return isset($SESSION->auth->signup->is_minor);
    }
}
