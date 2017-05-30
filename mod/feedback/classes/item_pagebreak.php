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
 * Dummy class with the same structure as feedbackitem plugins that represents the page break.
 */
class mod_feedback_item_pagebreak extends mod_feedback_item_base {

    public function __construct() {
        $this->type = 'pagebreak';
    }

    public function show_editform() {
    }

    /**
     * Checks if the editing form was cancelled
     * @return bool
     */
    public function is_cancelled() {
    }
    public function get_data() {
    }
    public function build_editform($item, $feedback, $cm) {
        global $DB;

        //check if there already is a pagebreak on the last position
        $lastposition = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));
        //if ($lastposition == feedback_get_last_break_position($feedback->id)) {
        //    return false;
        //}

        // TODO!

        $this->item = new stdClass();
        $this->item->feedback = $feedback->id;

        $this->item->template = 0;

        $this->item->name = '';

        $this->item->presentation = '';
        $this->item->hasvalue = 0;

        $this->item->typ = $this->type;
        $this->item->position = $lastposition + 1;

        $this->item->required=0;
    }
    public function save_item() {
    }
    public function create_value($data) {
    }
    public function get_hasvalue() {
        return 0;
    }
    public function excelprint_item(&$worksheet, $row_offset,
                                    $xls_formats, $item,
                                    $groupid, $courseid = false) {
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
    }
    public function get_printval($item, $value) {
    }
    public function can_switch_require() {
        return false;
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    public function complete_form_element($item, $form) {
        $form->add_form_element($item,
            ['static',
                $item->typ.'_'.$item->id,
                '',
                html_writer::empty_tag('hr', ['class' => 'feedback_pagebreak', 'id' => 'feedback_item_' . $item->id])
            ]);
    }

    /**
     * Returns the list of actions allowed on this item in the edit mode
     *
     * @param stdClass $item
     * @param stdClass $feedback
     * @param cm_info $cm
     * @return action_menu_link[]
     */
    public function edit_actions($item, $feedback, $cm) {
        $actions = array();
        $strdelete = get_string('delete_pagebreak', 'feedback');
        $actions['delete'] = new action_menu_link_secondary(
            new moodle_url('/mod/feedback/edit.php', array('id' => $cm->id, 'deleteitem' => $item->id, 'sesskey' => sesskey())),
            new pix_icon('t/delete', $strdelete, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $strdelete,
            array('class' => 'editing_delete', 'data-action' => 'delete')
        );
        return $actions;
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
        return;
    }

    /**
     * Prepares the item for export
     *
     * @param stdClass $item
     * @return array
     */
    public function prepare_for_export($item) {
        return [];
    }

    /**
     * Imports a feedback item
     *
     * @param array $data item data from the XML file, result of xmlize()
     * @param stdClass $feedback
     * @param int $version version of the export file
     * @param array $idsmap map of item ids in the export file and in the current feedback (for restoring dependencies)
     * @return bool|int id of the new feedback item or false if nothing imported
     */
    public function import($data, $feedback, $version, $idsmap) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/feedback/lib.php');
        return feedback_create_pagebreak($feedback->id);
    }
}
