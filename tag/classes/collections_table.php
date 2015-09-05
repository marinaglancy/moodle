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
 *
 *
 * @package   core_tag
 * @copyright 2015 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 *
 * @package   core_tag
 * @copyright 2015 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_tag_collections_table extends html_table {

    public function __construct($pageurl) {
        global $OUTPUT;
        parent::__construct();

        $this->attributes['class'] = 'generaltable tag-collections-table';

        $this->head = array(
            get_string('name'),
            get_string('component', 'tag'),
            get_string('tagareas', 'core_tag'),
            ''
        );

        $this->data = array();

        $tagcolls = core_tag_collection::get_collections();
        $idx = 0;
        foreach ($tagcolls as $tagcoll) {
            $actions = '';
            $name = core_tag_collection::display_name($tagcoll);
            if (!$tagcoll->isdefault) {

                $url = new moodle_url($pageurl, array('sesskey' => sesskey(), 'tc' => $tagcoll->id));
                // Move up.
                if ($idx > 1) {
                    $url->param('action', 'collmoveup');
                    $actions .= $OUTPUT->action_icon($url, new pix_icon('t/up', get_string('moveup')));
                }
                // Move down.
                if ($idx < count($tagcolls) - 1) {
                    $url->param('action', 'collmovedown');
                    $actions .= $OUTPUT->action_icon($url, new pix_icon('t/down', get_string('movedown')));
                }
                if (empty($tagcoll->component)) {
                    // Edit.
                    $url->param('action', 'colledit');
                    $actions .= $OUTPUT->action_icon($url, new pix_icon('t/edit', get_string('edittagcoll', 'tag', $name)));
                    // Delete.
                    $url->param('action', 'colldelete');
                    $actions .= $OUTPUT->action_icon($url, new pix_icon('t/delete', get_string('delete')));
                }
            }
            $manageurl = new moodle_url('/tag/manage.php', array('tc' => $tagcoll->id));
            $component = '';
            if ($tagcoll->component) {
                $component = ($tagcoll->component === 'core' || preg_match('/^core_/', $tagcoll->component)) ?
                    get_string('coresystem') : get_string('pluginname', $tagcoll->component);
            }
            $this->data[] = array(
                html_writer::link($manageurl, $name),
                $component,
                join(', ', core_tag_collection::get_areas_names($tagcoll->id)),
                $actions);
            $idx++;
        }

    }
}