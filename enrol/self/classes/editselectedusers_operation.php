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
 * Contains class enrol_self_editselectedusers_operation
 *
 * @copyright 2016 Marina Glancy
 * @package   enrol_self
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * A bulk operation for the self enrolment plugin to edit selected users.
 *
 * @copyright 2016 Marina Glancy
 * @package   enrol_self
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_self_editselectedusers_operation extends enrol_bulk_enrolment_operation {

    /**
     * Returns the title to display for this bulk operation.
     *
     * @return string
     */
    public function get_title() {
        return get_string('editselectedusers', 'enrol_self');
    }

    /**
     * Returns the identifier for this bulk operation. This is the key used when the plugin
     * returns an array containing all of the bulk operations it supports.
     */
    public function get_identifier() {
        return 'editselectedusers';
    }

    /**
     * Processes the bulk operation request for given users with the provided properties.
     *
     * @param course_enrolment_manager $manager
     * @param array $users array of users and their enrolments as returned
     *     by {@link course_enrolment_manager::get_users_enrolments()}
     * @param stdClass $properties The data returned by the form, see get_form()
     * @return bool false if capability/plugin check failed, true otherwise
     */
    public function process(course_enrolment_manager $manager, array $users, stdClass $properties) {

        if (!has_capability("enrol/self:manage", $manager->get_context())) {
            return false;
        }

        // From array of users get the list of enrol instances and user enrolments to update.
        // This should be only one enrol instance but we build an array to be sure.
        $instances = [];
        $enrolments = [];
        foreach ($users as $user) {
            foreach ($user->enrolments as $enrolment) {
                if ($this->plugin->allow_manage($enrolment)) {
                    $instances[$enrolment->enrolmentinstance->id] = $enrolment->enrolmentinstance;
                    $enrolment->userid = $user->id;
                    $enrolments[$enrolment->enrolmentinstance->id][$enrolment->id] = $enrolment;
                }
            }
        }

        foreach ($instances as $instance) {
            $this->plugin->bulk_update_user_enrol($instance, $enrolments[$instance->id],
                $properties->status, $properties->timestart, $properties->timeend);
        }

        return true;
    }

    /**
     * Returns a enrol_bulk_enrolment_operation extension form to be used
     * in collecting required information for this operation to be processed.
     *
     * @param string|moodle_url|null $defaultaction
     * @param mixed $defaultcustomdata
     * @return enrol_self_editselectedusers_form
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        return new enrol_self_editselectedusers_form($defaultaction, $defaultcustomdata);
    }
}
