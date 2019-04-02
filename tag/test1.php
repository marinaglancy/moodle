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
 * @package    core_tag
 * @category   tag
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');

require_login();

$PAGE->set_url(new moodle_url('/tag/test1.php'));
$PAGE->set_context(context_system::instance());

$form = new \core_tag\testform();
echo $OUTPUT->header();
echo $OUTPUT->heading('Test1 - normal form');
if ($form->is_cancelled()) {
    echo "<p>Cancelled....</p>";
} else  if ($data = $form->get_data()) {
    print_object($data);
} else {
    $form->display();
}
echo $OUTPUT->footer();