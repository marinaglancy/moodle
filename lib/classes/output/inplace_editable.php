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
 * Contains class \core\output\inplace_editable
 *
 * @package    core
 * @category   output
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

use templatable;
use renderable;

/**
 * Class allowing to quick edit a title inline
 *
 * @package    core
 * @category   output
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable implements templatable, renderable {

    /**
     * @var string component responsible for diplsying/updating
     */
    protected $component = null;

    /**
     * @var string itemtype inside the component
     */
    protected $itemtype = null;

    /**
     * @var int identifier of the editable element (usually database id)
     */
    protected $itemid = null;

    /**
     * @var string value of the editable element as it is present in the database
     */
    protected $value = null;

    /**
     * @var string value of the editable element as it should be displayed,
     * must be formatted and may contain links or other html tags
     */
    protected $displayvalue = null;

    /**
     * @var string label for the input element (for screenreaders)
     */
    protected $editlabel = null;

    /**
     * @var string hint for the input element (for screenreaders)
     */
    protected $edithint = null;

    /**
     * @var bool indicates if the current user is allowed to edit this element - set in constructor after permissions are checked
     */
    protected $editable = false;

    /**
     * Constructor.
     *
     * Override to set the other properties that can be retrieved from identifier and environment.
     * Constructor can throw exceptions if identifier is not valid.
     *
     * @param string $component
     * @param string $itemtype
     * @param int $itemid
     * @param bool $editable
     * @param string $displayvalue
     * @param string $value
     * @param string $edithint
     * @param string $editlabel
     */
    public function __construct($component, $itemtype, $itemid, $editable,
            $displayvalue, $value = null, $edithint = null, $editlabel = null) {
        $this->component = $component;
        $this->itemtype = $itemtype;
        $this->itemid = $itemid;
        $this->editable = $editable;
        $this->displayvalue = $displayvalue;
        $this->value = $value;
        $this->edithint = $edithint;
        $this->editlabel = $editlabel;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        if (!$this->editable) {
            return array(
                'displayvalue' => $this->displayvalue
            );
        }

        return array(
            'component' => $this->component,
            'itemtype' => $this->itemtype,
            'itemid' => $this->itemid,
            'displayvalue' => (string)$this->displayvalue,
            'value' => (string)$this->value,
            'edithint' => (string)$this->edithint,
            'editlabel' => (string)$this->editlabel,
        );
    }
}