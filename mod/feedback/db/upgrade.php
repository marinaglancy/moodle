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
 * This file keeps track of upgrades to the feedback module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package mod_feedback
 * @author Andreas Grabs
 * @copyright Andreas Grabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_feedback_upgrade is the function that upgrades
 * the feedback module database when is needed
 *
 * This function is automaticly called when version number in
 * version.php changes.
 *
 * @param int $oldversion New old version number.
 *
 * @return boolean
 */
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

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2018051402) {

        // Changing type of field name on table feedback_item to text.
        $table = new xmldb_table('feedback_item');
        $field = new xmldb_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'template');
        // Launch change of type for field name.
        $dbman->change_field_type($table, $field);

        // Define field nameformat to be added to feedback_item.
        $table = new xmldb_table('feedback_item');
        $field = new xmldb_field('nameformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'name');

        // Conditionally launch add field nameformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // For labels move the text from field "presentation" to "name" and set the
        // nameformat to FORMAT_HTML as it was always assumed for "presentation".
        // Checking and changing nameformat also ensures that this conversion can only run once per record even if interrupted.
        $DB->execute('UPDATE {feedback_item} SET name = presentation, nameformat = 1 WHERE typ = ? and nameformat = 0',
            ['label']);
        $DB->execute('UPDATE {feedback_item} SET presentation = ? WHERE typ = ?', ['', 'label']);

        // Now update nameformat for all other existing items.
        $DB->execute('UPDATE {feedback_item} SET nameformat = ? WHERE typ <> ?', [1/*FORMAT_HTML*/, 'label']);

        // Feedback savepoint reached.
        upgrade_mod_savepoint(true, 2018051402, 'feedback');
    }

    return true;
}
