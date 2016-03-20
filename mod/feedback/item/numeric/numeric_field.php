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
 * File contains definition of class mod_feedback_item_numeric_input
 *
 * @package    mod_feedback
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("HTML/QuickForm/text.php");

/**
 * Form element for handling numeric value input
 *
 * @package    mod_feedback
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_item_numeric_input extends HTML_QuickForm_text {

    /**
     * Sets the value of the form element
     *
     * @param     string    $value      Default value of the form element
     * @return    void
     */
    function setValue($value) {
        echo "setting value $value<br>";
        $this->updateAttributes(array('value'=>$value));
    }

   /**
    * We don't need values from button-type elements (except submit) and files
    */
    function exportValue(&$submitValues, $assoc = false)
    {
        $type = $this->getType();
        if ('reset' == $type || 'image' == $type || 'button' == $type || 'file' == $type) {
            return null;
        } else {
            return parent::exportValue($submitValues, $assoc);
        }
    }
}

MoodleQuickForm::registerElementType('feedbacknumeric', $CFG->dirroot.'/mod/feedback/item/numeric/numeric_field.php', 'mod_feedback_item_numeric_input');
