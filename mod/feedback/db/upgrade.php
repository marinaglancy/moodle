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

// This file keeps track of upgrades to
// the feedback module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die();

function xmldb_feedback_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2017032800) {

        // Delete duplicated records in feedback_completed. We just keep the last record of completion.
        // Related values in feedback_value won't be deleted (they won't be used and can be kept there as a backup).
        $sql = "SELECT MAX(id) as maxid, userid, feedback, courseid
                  FROM {feedback_completed}
                 WHERE userid <> 0 AND anonymous_response = :notanonymous
              GROUP BY userid, feedback, courseid
                HAVING COUNT(id) > 1";
        $params = ['notanonymous' => 2]; // FEEDBACK_ANONYMOUS_NO.

        $duplicatedrows = $DB->get_recordset_sql($sql, $params);
        foreach ($duplicatedrows as $row) {
            $DB->delete_records_select('feedback_completed', 'userid = ? AND feedback = ? AND courseid = ? AND id <> ?'.
                                                           ' AND anonymous_response = ?', array(
                                           $row->userid,
                                           $row->feedback,
                                           $row->courseid,
                                           $row->maxid,
                                           2, // FEEDBACK_ANONYMOUS_NO.
            ));
        }
        $duplicatedrows->close();

        // Feedback savepoint reached.
        upgrade_mod_savepoint(true, 2017032800, 'feedback');
    }

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2018051401) {

        // From Moodle 3.6 capability 'mod/feedback:complete' can be given to guest role. Before 3.6 even if it was set
        // to "allow", it did not apply. It is possible that administrator/teacher tried to set it, saw that it was not working
        // but did not bother to unset it. In order to keep the existing functionality we need to unset it.

        // Let's find the guest role same way as get_guest_role() does it but without calling API functions.
        $role = null;
        if (!empty($CFG->guestroleid)) {
            $role = $DB->get_record('role', ['id' => $CFG->guestroleid]);
        }
        if (!$role) {
            $role = $DB->get_record('role', ['archetype' => 'guest'], '*', IGNORE_MULTIPLE);
        }
        // Unset the CAP_ALLOW for the capability 'mod/feedback:complete' for this role in any context.
        if ($role) {
            $DB->delete_records('role_capabilities', ['capability' => 'mod/feedback:complete', 'roleid' => $role->id, 'permission' => 1]);
        }

        // Deprecate setting $CFG->feedback_allowfullanonymous . If it was not set before upgrade never show it in the settings.
        if (!empty($CFG->feedback_allowfullanonymous)) {
            set_config('feedback_show_allowfullanonymous', 1);
        }

        // Feedback savepoint reached.
        upgrade_mod_savepoint(true, 2018051401, 'feedback');
    }

    return true;
}
