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

abstract class feedback_item_base {
    protected $type;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->init();
    }

    //this function only can used after the call of build_editform()
    public function show_editform() {
        $this->item_form->display();
    }

    public function is_cancelled() {
        return $this->item_form->is_cancelled();
    }

    public function get_data() {
        if ($this->item = $this->item_form->get_data()) {
            return true;
        }
        return false;
    }

    public function value_type() {
        return PARAM_RAW;
    }

    public function value_is_array() {
        return false;
    }

    abstract public function init();
    abstract public function build_editform($item, $feedback, $cm);
    abstract public function save_item();
    abstract public function check_value($value, $item);
    abstract public function create_value($data);
    abstract public function compare_value($item, $dbvalue, $dependvalue);
    abstract public function get_presentation($data);
    abstract public function get_hasvalue();
    abstract public function can_switch_require();

    /**
     * @param object $worksheet a reference to the pear_spreadsheet-object
     * @param integer $row_offset
     * @param object $item the db-object from feedback_item
     * @param integer $groupid
     * @param integer $courseid
     * @return integer the new row_offset
     */
    abstract public function excelprint_item(&$worksheet, $row_offset,
                                      $xls_formats, $item,
                                      $groupid, $courseid = false);

    /**
     * @param $item the db-object from feedback_item
     * @param string $itemnr
     * @param integer $groupid
     * @param integer $courseid
     * @return integer the new itemnr
     */
    abstract public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false);

    /**
     * @param object $item the db-object from feedback_item
     * @param string $value a item-related value from feedback_values
     * @return string
     */
    abstract public function get_printval($item, $value);

    /**
     * returns an Array with three values(typ, name, XXX)
     * XXX is also an Array (count of responses on type $this->type)
     * each element is a structure (answertext, answercount)
     * @param $item the db-object from feedback_item
     * @param $groupid if given
     * @param $courseid if given
     * @return array
     */
    abstract public function get_analysed($item, $groupid = false, $courseid = false);

    /**
     * print the item at the edit-page of feedback
     *
     * @global object
     * @param object $item
     * @return void
     */
    abstract public function print_item_preview($item);

    /**
     * print the item at the complete-page of feedback
     *
     * @global object
     * @param object $item
     * @param string $value
     * @param bool $highlightrequire
     * @return void
     */
    abstract public function print_item_complete($item, $value = '', $highlightrequire = false);

    /**
     * print the item at the complete-page of feedback
     *
     * @global object
     * @param object $item
     * @param string $value
     * @return void
     */
    abstract public function print_item_show_value($item, $value = '');

    /**
     * cleans the userinput while submitting the form
     *
     * @param mixed $value
     * @return mixed
     */
    abstract public function clean_input_value($value);

    /**
     * Returns item name ready for display on pages such as complete form or preview
     *
     * @param stdClass $item
     * @param string $itemname optional name of the item to use instead of $item->name
     * @return string
     */
    protected function item_formatted_name($item, $itemname = null) {
        global $OUTPUT;
        if ($itemname === null) {
            $itemname = format_text($item->name, FORMAT_HTML, array('noclean' => true, 'para' => false));
        }
        $requiredmark = '';
        if ($item->required == 1) {
            $requiredmark = '<img class="req" title="'.get_string('requiredelement', 'form').'" alt="'.
                get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req') .'" />';
        }
        return $itemname . $requiredmark;
    }

    /**
     * Returns item label and item name ready for display on pages such as Edit, Analysis and Response
     *
     * @param stdClass $item
     * @param string $itemname optional name of the item to use instead of $item->name
     * @return string
     */
    protected function item_label($item, $itemname = null) {
        if (strval($item->label) !== '') {
            return '('. format_string($item->label).') ';
        }
        return '';
    }

    /**
     * Returns the item dependency ready for display on pages such as Edit, Analysis, view response
     *
     * @param stdClass $item
     * @return string
     */
    protected function item_depend_value($item) {
        global $DB;
        $rv = '';
        if ($item->dependitem && ($dependitem = $DB->get_record('feedback_item', array('id' => $item->dependitem)))) {
            $rv .= ' <span class="feedback_depend">';
            $rv .= '('.format_string($dependitem->label).'-&gt;'.$item->dependvalue.')';
            $rv .= '</span>';
        }
        return $rv;
    }
}

//a dummy class to realize pagebreaks
class feedback_item_pagebreak extends feedback_item_base {
    protected $type = "pagebreak";

    public function show_editform() {
    }
    public function is_cancelled() {
    }
    public function get_data() {
    }
    public function init() {
    }
    public function build_editform($item, $feedback, $cm) {
    }
    public function save_item() {
    }
    public function check_value($value, $item) {
    }
    public function create_value($data) {
    }
    public function compare_value($item, $dbvalue, $dependvalue) {
    }
    public function get_presentation($data) {
    }
    public function get_hasvalue() {
    }
    public function excelprint_item(&$worksheet, $row_offset,
                            $xls_formats, $item,
                            $groupid, $courseid = false) {
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
    }
    public function get_printval($item, $value) {
    }
    public function get_analysed($item, $groupid = false, $courseid = false) {
    }
    public function print_item_preview($item) {
    }
    public function print_item_complete($item, $value = '', $highlightrequire = false) {
    }
    public function print_item_show_value($item, $value = '') {
    }
    public function can_switch_require() {
    }
    public function value_type() {
    }
    public function clean_input_value($value) {
    }

}
