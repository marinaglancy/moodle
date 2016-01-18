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
 * Contains class core_tag\inplace_editable_saver
 *
 * @package   core_tag
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tag;

use core_tag_tag;
use context_system;
use moodle_exception;
use html_writer;
use stdClass;
use lang_string;

/**
 * Class to process inplace editing of the tag name on "Manage tags" page
 *
 * @package   core_tag
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable_saver implements \core\inplace_editable_saver_base {

    /**
     * Returns the tag name ready for inline editing
     *
     * @param core_tag_tag|stdClass $tag
     * @return \core\output\inplace_editable
     */
    public function render_tag_name($tag) {
        $editable = has_capability('moodle/tag:manage', context_system::instance());
        $edithint = new lang_string('editname', 'core_tag');
        $editlabel = new lang_string('newnamefor', 'core_tag', $tag->rawname);
        $value = $tag->rawname;
        $displayvalue = html_writer::link(core_tag_tag::make_url($tag->tagcollid, $tag->rawname),
            core_tag_tag::make_display_name($tag));

        return new \core\output\inplace_editable('core_tag', 'tagname', $tag->id, $editable, $displayvalue,
            $value, $edithint, $editlabel);
    }

    /**
     * Updates the value in the database and modifies this object respectively.
     *
     * @param string $itemtype
     * @param int $itemid
     * @param string $newvalue
     * @return \core\output\inplace_editable
     */
    public function update_value($itemtype, $itemid, $newvalue) {
        if ($itemtype === 'tagname') {
            $newvalue = clean_param($newvalue, PARAM_TAG);
            require_capability('moodle/tag:manage', context_system::instance());
            $tag = core_tag_tag::get($itemid, '*', MUST_EXIST);
            if (!empty($newvalue) && $newvalue !== $tag->rawname) {
                if (($existing = core_tag_tag::get_by_name($tag->tagcollid, $newvalue, 'id')) &&
                    $existing->id != $tag->id) {
                    throw new moodle_exception('namesalreadybeeingused', 'core_tag');
                }
                $tag->update(array('rawname' => $newvalue));
            }
            return $this->render_tag_name($tag);
        } else {
            throw new coding_exception('Unrecognised itemtype');
        }
    }
}
