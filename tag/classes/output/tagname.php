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
 * Contains class core_tag\output\tagname
 *
 * @package   core_tag
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tag\output;

use renderer_base;
use core_tag_tag;
use context_system;
use moodle_exception;
use html_writer;
use stdClass;

/**
 * Class to preapare a list of tags for display, usually the list of tags some entry is tagged with.
 *
 * @package   core_tag
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tagname extends \core\output\editabletitle {

    /**
     * @var core_tag_tag|stdClass tag object
     */
    protected $tag;

    /**
     * Constructor.
     *
     * @param int|core_tag_tag|stdClass $identifier either tag object or tag id
     */
    public function __construct($identifier) {
        if ($identifier instanceof core_tag_tag) {
            $this->tag = $identifier;
        } else if (is_object($identifier)) {
            $this->tag = $identifier;
        } else {
            $this->tag = core_tag_tag::get($identifier, '*', MUST_EXIST);
        }
        parent::__construct($this->tag->id);
        $this->editable = has_capability('moodle/tag:manage', context_system::instance());
    }

    /**
     * Updates the value in the database and modifies this object respectively.
     *
     * @param string $newvalue
     */
    public function update($newvalue) {
        $newvalue = clean_param($newvalue, PARAM_TAG);
        require_capability('moodle/tag:manage', context_system::instance());
        if (!empty($newvalue)) {
            if (($existing = core_tag_tag::get_by_name($this->tag->tagcollid, $newvalue, 'id')) &&
                    $existing->id != $this->tag->id) {
                throw new moodle_exception('namesalreadybeeingused', 'core_tag');
            }
            if (!$this->tag instanceof core_tag_tag) {
                $this->tag = core_tag_tag::get($this->tag->id);
            }
            $this->tag->update(array('rawname' => $newvalue));
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        $this->edithint = get_string('editname', 'core_tag');
        $this->editlabel = get_string('newnamefor', 'core_tag', $this->tag->rawname);
        $this->value = $this->tag->rawname;
        $this->displayvalue = html_writer::link(core_tag_tag::make_url($this->tag->tagcollid, $this->tag->rawname),
            core_tag_tag::make_display_name($this->tag));

        return parent::export_for_template($output);
    }
}
