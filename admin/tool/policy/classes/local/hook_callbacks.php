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

namespace tool_policy\local;

/**
 * Hook callbacks for tool_policy
 *
 * @package    tool_policy
 * @copyright  2023 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Load policy message for guests.
     *
     * @param \core\hook\output\before_standard_html_head $hook
     */
    public static function before_standard_html_head(\core\hook\output\before_standard_html_head $hook): void {
        global $CFG, $PAGE, $USER;

        $message = '';
        if (!empty($CFG->sitepolicyhandler)
                && $CFG->sitepolicyhandler == 'tool_policy'
                && empty($USER->policyagreed)
                && (isguestuser() || !isloggedin())) {
            $output = $PAGE->get_renderer('tool_policy');
            try {
                $page = new \tool_policy\output\guestconsent();
                $message = $output->render($page);
            } catch (\dml_read_exception $e) {
                // During upgrades, the new plugin code with new SQL could be in place but the DB not upgraded yet.
                $message = '';
            }
        }

        $hook->add_html($message);
    }
}
