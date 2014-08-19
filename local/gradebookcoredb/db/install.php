<?php

defined('MOODLE_INTERNAL') || die();

// This logic ultimately needs to go into lib/db/upgrade.php.

function xmldb_local_gradebookcoredb_install() {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // Define fields weight, weightoverride, and extracredit to be added to grade_items.

    $table = new xmldb_table('grade_items');

    $field = new xmldb_field('weight', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'needsupdate');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('weightoverride', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'weight');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('extracredit', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'weightoverride');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $table2 = new xmldb_table('grade_grades');

    // After timemodified may not be the best place. Re-evaluate when this code is going into core.
    $field = new xmldb_field('weight', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'timemodified');
    if (!$dbman->field_exists($table2, $field)) {
        $dbman->add_field($table2, $field);
    }

}

