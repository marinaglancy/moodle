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

namespace core\event;

/**
 * Event when new user profile is created during course restore
 *
 * @package    core
 * @since      Moodle 4.2
 * @copyright  2023 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_created_on_restore extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'user';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventusercreatedonrestore');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $courseid = $this->other['courseid'] ?? 0;
        return "The user with id '$this->userid' created the user with id '$this->objectid' ".
            "during restore of the course with id '$courseid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/user/view.php', ['id' => $this->objectid]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            debugging('The \'relateduserid\' value must be specified in the event.', DEBUG_DEVELOPER);
            $this->relateduserid = $this->objectid;
        }
    }

    /**
     * Create instance of event.
     *
     * @param int $userid id of user
     * @param string $restoreid
     * @return user_created
     */
    public static function create_from_user_id(int $userid, string $restoreid, int $courseid) {
        $data = [
            'objectid' => $userid,
            'relateduserid' => $userid,
            'context' => \context_user::instance($userid),
            'other' => ['restoreid' => $restoreid, 'courseid' => $courseid],
        ];

        // Create user_created event.
        $event = self::create($data);
        return $event;
    }

    public static function get_objectid_mapping() {
        return ['db' => 'user', 'restore' => 'user'];
    }
}
