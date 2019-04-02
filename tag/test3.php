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

$PAGE->set_url(new moodle_url('/tag/test3.php'));
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->heading('Test3 - embedded form');
echo html_writer::div(html_writer::link('#', 'Load form', ['data-action' => 'loadform']));
echo html_writer::div('', '', ['data-region' => 'form']);
$PAGE->requires->js_amd_inline("
require(['core_form/ajaxform'], function(AjaxForm) {
    form = new AjaxForm('[data-region=form]', 'core_tag\\\\testform', {});
    form.onSubmitSuccess = function(response) {
        console.log(response);
        document.querySelector('[data-region=form]').innerHTML = '<pre>'+JSON.stringify(response)+'</pre>';
    }
    document.querySelector('[data-action=loadform]').addEventListener('click', function(e) {
        e.preventDefault();
        form.load();
    });
});");

echo $OUTPUT->footer();