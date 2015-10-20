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
 * A scheduled task for external db user sync.
 *
 * @package    auth_db
 * @copyright  2015 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_db\task;

/**
 * A scheduled task class for external db user sync.
 *
 * @copyright  2015 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('synctask', 'auth_db');
    }

    /**
     * Run users sync.
     */
    public function execute() {
        global $CFG;
        if (is_enabled_auth('db')) {

            if ($CFG->debugdeveloper) {
                $trace = new \text_progress_trace();
            } else {
                $trace = new \null_progress_trace();
            }

            $auth = get_auth_plugin('db');
            $doupdates = get_config('auth/db', 'doupdates');
            $auth->sync_users($trace, $doupdates);
        }
    }

}
