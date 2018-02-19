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
 * Auth session.
 *
 * @package    core_auth
 * @copyright  2018 Mihail Geshoski (mihail@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_auth\session;

defined('MOODLE_INTERNAL') || die();


/**
 * Auth session base class.
 *
 * @copyright  2018 Mihail Geshoski (mihail@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class auth_session_base {

    /**
     * Create a session.
     */
    abstract public static function create();

    /**
     * Get the creation time of a session.
     *
     * @return int
     */
    abstract public static function get_timecreated();

    /**
     * Check if a session is set.
     *
     * @return bool
     */
    abstract public static function is_set();

    /**
     * Destroy the session.
     */
    abstract public static function destroy();

    /**
     * Check if a session is valid.
     *
     * @return bool
     */
    public static function is_valid() {

        return time() - static::get_timecreated() < static::MAX_SESSION_TIME;
    }
}
