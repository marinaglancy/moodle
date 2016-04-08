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
 * Retrieve the user memberships (AJAX call from a user selector, see M.core_user.init_user_selector.load_user_summaries
 *
 * @package core_user
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/group/user_memberships.php');

echo $OUTPUT->header();

// Check access.
require_login();
require_sesskey();

// Potential members (comma-separated string of potential members IDs)
$potentialmembersids = required_param('potential', PARAM_RAW);
$potentialmembersids = explode(",", $potentialmembersids);

// Get and validate the selectorid parameter.
$selectorhash = required_param('selectorid', PARAM_ALPHANUM);
if (!isset($USER->userselectors[$selectorhash])) {
    print_error('unknownuserselector');
}

// Get the options.
$options = $USER->userselectors[$selectorhash];

// Create the appropriate userselector.
$classname = $options['class'];
unset($options['class']);
$name = $options['name'];
unset($options['name']);
if (isset($options['file'])) {
    require_once($CFG->dirroot . '/' . $options['file']);
    unset($options['file']);
}
$userselector = new $classname($name, $options);

// Do the query and output the results.
$results = $userselector->get_user_summaries($options['courseid'], $potentialmembersids);
$json = array();

foreach ($results as $userid => $usergrouplist) {
    $groupdata = array('userid' => $userid, 'usergrouplist' => $usergrouplist);
    $json[] = $groupdata;
}

echo json_encode(array('results' => $json));
