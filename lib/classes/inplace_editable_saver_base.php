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
 * Contains interface \core\inplace_editable_saver_base
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core;

/**
 * Interface for implementing callbacks to quick edit a title inline
 *
 * Component may implement a class \componentname\inplace_editable_saver that
 * implements this interface and use it process changes when user edits
 * items in this component inline.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface inplace_editable_saver_base {
    /**
     * Updates the value in the database and modifies this object respectively.
     *
     * ALWAYS check user permissions before performing an update! Throw exceptions if permissions are not sufficient
     * or value is not legit. Remember that $newvalue is a raw user input and normally must be cleaned and validated
     * inside this function.
     *
     * @param string $itemtype
     * @param int $itemid
     * @param mixed $newvalue
     * @return \core\output\inplace_editable
     */
    public function update_value($itemtype, $itemid, $newvalue);
}