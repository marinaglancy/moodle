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
 * Contains class \core\output\editabletitle
 *
 * @package    core
 * @category   output
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

use templatable;

/**
 * Class allowing to quick edit a title inline
 *
 * @package    core
 * @category   output
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class editabletitle implements templatable {

    /**
     * @var int|string identifier of the editable element (usually database id)
     */
    protected $identifier = null;

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
     * @param mixed $identifier
     */
    public function __construct($identifier) {
        $this->identifier = $identifier;
    }

    /**
     * Updates the value in the database and modifies this object respectively.
     *
     * ALWAYS check user permissions before performing an update! Throw exceptions if permissions are not sufficient
     * or value is not legit.
     *
     * @param mixed $newvalue
     */
    public abstract function update($newvalue);

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

        $callback = get_class($this);
        return array(
            'displayvalue' => $this->displayvalue,
            'callback' => $callback,
            'value' => $this->value,
            'identifier' => $this->identifier,
            'edithint' => $this->edithint,
            'hash' => md5($callback . $this->identifier),
            'editlabel' => $this->editlabel,
        );
    }
}