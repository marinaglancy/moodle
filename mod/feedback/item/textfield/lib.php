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

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

class feedback_item_textfield extends feedback_item_base {
    protected $type = "textfield";

    public function build_editform($item, $feedback, $cm) {
        global $DB, $CFG;
        require_once('textfield_form.php');

        //get the lastposition number of the feedback_items
        $position = $item->position;
        $lastposition = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));
        if ($position == -1) {
            $i_formselect_last = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        } else {
            $i_formselect_last = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;

        $size_and_length = explode('|', $item->presentation);

        if (isset($size_and_length[0]) AND $size_and_length[0] >= 5) {
            $itemsize = $size_and_length[0];
        } else {
            $itemsize = 30;
        }

        $itemlength = isset($size_and_length[1]) ? $size_and_length[1] : 255;

        $item->itemsize = $itemsize;
        $item->itemmaxlength = $itemlength;

        //all items for dependitem
        $feedbackitems = feedback_get_depend_candidates_for_item($feedback, $item);
        $commonparams = array('cmid' => $cm->id,
                             'id' => isset($item->id) ? $item->id : null,
                             'typ' => $item->typ,
                             'items' => $feedbackitems,
                             'feedback' => $feedback->id);

        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position,
                            'nameoptions' => $this->get_name_editor_options($item));

        $this->item_form = new feedback_textfield_form('edit_item.php', $customdata);
    }

    public function save_item() {
        global $DB;

        if (!$this->get_data()) {
            return false;
        }
        $item = $this->item;

        if (isset($item->clone_item) AND $item->clone_item) {
            $item->id = ''; //to clone this item
            $item->position++;
        }

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->name = '';
            $item->id = $DB->insert_record('feedback_item', $item);
        }

        $nameeditoroptions = $this->get_name_editor_options($item);
        $item = file_postupdate_standard_editor($item,
            'name',
            $nameeditoroptions,
            $nameeditoroptions['context'],
            'mod_feedback',
            'item',
            $item->id);
        $DB->update_record('feedback_item', $item);

        return $DB->get_record('feedback_item', array('id'=>$item->id));
    }


    /**
     * Helper function for collected data for exporting to excel
     *
     * @param stdClass $item the db-object from feedback_item
     * @param int $groupid
     * @param int $courseid
     * @param bool $forexport prepare for export or for display (for example: newlines should be converted to <br> for display but not for export)
     * @return stdClass
     */
    public function get_analysis($item, $groupid = false, $courseid = false, $forexport = false) {
        $analyseditem = parent::get_analysis($item, $groupid, $courseid, $forexport);
        $analyseditem->individualdata = [];

        $values = feedback_get_group_values($item, $groupid, $courseid);
        foreach ($values as $value) {
            $analyseditem->individualdata[] = $this->get_display_value($item, $value->value, $forexport);
        }
        array_filter($analyseditem->individualdata, function($el) {
            strval($el) !== '';
        });
        $analyseditem->hasdata = !empty($analyseditem->individualdata);
        return $analyseditem;
    }

    public function get_display_value($item, $value, $forexport = false) {
        $value = parent::get_display_value($item, $value, $forexport);
        if ($forexport) {
            // Method create_value() applies s() to the value stored in the database. Before exporting we need to revert it.
            return htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML401 | ENT_SUBSTITUTE);
        } else {
            return $value;
        }
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false) {

        $analyseditem = $this->get_analysis($item, $groupid, $courseid, true);

        $worksheet->write_string($row_offset, 0, $analyseditem->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $analyseditem->shortname, $xls_formats->head2);
        if (!empty($analyseditem->individualdata)) {
            foreach ($analyseditem->individualdata as $value) {
                $worksheet->write_string($row_offset, 2, $value, $xls_formats->default);
                $row_offset++;
            }
        }
        $row_offset++;
        return $row_offset;
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    public function complete_form_element($item, $form) {
        $name = $this->get_display_name($item);
        $inputname = $item->typ . '_' . $item->id;
        list($size, $maxlength) = explode ("|", $item->presentation);
        $form->add_form_element($item,
                ['text', $inputname, $name, ['maxlength' => $maxlength, 'size' => $size]], true, false);
        $form->set_element_default($inputname, $this->get_display_value($item, $form->get_item_value($item), true));
        $form->set_element_type($inputname, PARAM_NOTAGS);

        $form->add_element_rule($inputname, get_string('maximumchars', '', $maxlength), 'maxlength', $maxlength, 'client');
    }

    /**
     * Converts the value from complete_form data to the string value that is stored in the db.
     * @param mixed $value element from mod_feedback_complete_form::get_data() with the name $item->typ.'_'.$item->id
     * @return string
     */
    public function create_value($value) {
        return s($value);
    }

    /**
     * Return the analysis data ready for external functions.
     *
     * @param stdClass $item     the item (question) information
     * @param int      $groupid  the group id to filter data (optional)
     * @param int      $courseid the course id (optional)
     * @return array an array of data with non scalar types json encoded
     * @since  Moodle 3.3
     */
    public function get_analysed_for_external($item, $groupid = false, $courseid = false) {

        $externaldata = array();
        $data = $this->get_analysis($item, $groupid, $courseid, true);

        if (is_array($data->individualdata)) {
            return $data->individualdata; // No need to json, scalar type.
        }
        return $externaldata;
    }
}
