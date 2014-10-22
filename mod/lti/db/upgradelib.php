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
 * LTI upgrade script.
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Update any custom parameter settings separated by semicolons.
 */
function mod_lti_upgrade_custom_separator() {
    global $DB;

    // Initialise parameter array.
    $params = array('semicolon' => ';', 'likecr' => "%\r%", 'likelf' => "%\n%", 'lf' => "\n");

    // Initialise NOT LIKE clauses to check for CR and LF characters.
    $notlikecr = $DB->sql_like('value', ':likecr', true, true, true);
    $notlikelf = $DB->sql_like('value', ':likelf', true, true, true);

    // Initialise NOT LIKE clauses to check for CR and LF characters.
    $notlikecr2 = $DB->sql_like('instructorcustomparameters', ':likecr', true, true, true);
    $notlikelf2 = $DB->sql_like('instructorcustomparameters', ':likelf', true, true, true);

    if ($DB->replace_all_text_supported()) {

        // Update any instances in the lti_types_config table.
        $sql = 'UPDATE {lti_types_config} ' .
               'SET value = REPLACE(value, :semicolon, :lf) ' .
               'WHERE (name = \'customparameters\') AND (' . $notlikecr . ') AND (' . $notlikelf . ')';
        $DB->execute($sql, $params);

        // Update any instances in the lti table.
        $sql = 'UPDATE {lti} ' .
               'SET instructorcustomparameters = REPLACE(instructorcustomparameters, :semicolon, :lf) ' .
               'WHERE (instructorcustomparameters IS NOT NULL) AND (' . $notlikecr2 . ') AND (' . $notlikelf2 . ')';
        $DB->execute($sql, $params);

    } else {

        // Update any instances in the lti_types_config table.
        $records = $DB->get_records_sql('SELECT id, value FROM {lti_types_config} ' .
            'WHERE (name = \'customparameters\') AND (' . $notlikecr . ') AND (' . $notlikelf . ')',
            $params);
        foreach ($records as $record) {
            $record->value = preg_replace('/;/', "\n", $record->value);
            $DB->update_record('lti_types_config', $record);
        }

        // Update any instances in the lti table.
        $records = $DB->get_records_sql('SELECT id, instructorcustomparameters FROM {lti} ' .
            'WHERE (instructorcustomparameters IS NOT NULL) AND (' . $notlikecr2 . ') AND (' . $notlikelf2 . ')',
            $params);
        foreach ($records as $record) {
            $record->instructorcustomparameters = preg_replace('/;/', "\n", $record->instructorcustomparameters);
            $DB->update_record('lti', $record);
        }

    }

}
