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

class feedback_item_info extends feedback_item_base {
    protected $type = "info";

    /** Mode recording response time (for non-anonymous feedbacks only) */
    const MODE_RESPONSETIME = 1;
    /** Mode recording current course */
    const MODE_COURSE = 2;
    /** Mode recording current course category */
    const MODE_CATEGORY = 3;

    /** Special constant to keep the current timestamp as value for the form element */
    const CURRENTTIMESTAMP = '__CURRENT__TIMESTAMP__';

    public function build_editform($item, $feedback, $cm) {
        global $DB, $CFG;
        require_once('info_form.php');

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

        $item->presentation = empty($item->presentation) ? self::MODE_COURSE : $item->presentation;
        $item->required = 0;

        //all items for dependitem
        $feedbackitems = feedback_get_depend_candidates_for_item($feedback, $item);
        $commonparams = array('cmid'=>$cm->id,
                             'id'=>isset($item->id) ? $item->id : null,
                             'typ'=>$item->typ,
                             'items'=>$feedbackitems,
                             'feedback'=>$feedback->id);

        // Options for the 'presentation' select element.
        $presentationoptions = array();
        if ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO || $item->presentation == self::MODE_RESPONSETIME) {
            // "Response time" is hidden anyway in case of anonymous feedback, no reason to offer this option.
            // However if it was already selected leave it in the dropdown.
            $presentationoptions[self::MODE_RESPONSETIME] = get_string('responsetime', 'feedback');
        }
        $presentationoptions[self::MODE_COURSE]  = get_string('course');
        $presentationoptions[self::MODE_CATEGORY]  = get_string('coursecategory');

        //build the form
        $this->item_form = new feedback_info_form('edit_item.php',
                                                  array('item'=>$item,
                                                  'common'=>$commonparams,
                                                  'positionlist'=>$positionlist,
                                                  'position' => $position,
                                                  'presentationoptions' => $presentationoptions,
                                                  'nameoptions' => $this->get_name_editor_options($item)));
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
     * Helper function for collected data, both for analysis page and export to excel
     *
     * @param stdClass $item the db-object from feedback_item
     * @param int|false $groupid
     * @param int $courseid
     * @param bool $forexport prepare for export or for display (for example: newlines should be converted to <br> for display but not for export)
     * @return stdClass
     */
    public function get_analysis($item, $groupid = false, $courseid = false, $forexport = false) {

        $analysed = parent::get_analysis($item, $groupid, $courseid, $forexport, $forexport);
        $values = feedback_get_group_values($item, $groupid, $courseid);
        $analysed->individualdata = [];
        $analysed->dataexternal = [];
        if ($values) {
            foreach ($values as $value) {
                $show = $this->get_display_value($item, $value->value, $forexport);
                $analysed->dataexternal[] = (object)['value' => $value->value, 'show' => $show];
                $analysed->individualdata[] = $show;
            }
        }
        array_filter($analysed->individualdata, function($el) {
            strval($el) !== '';
        });
        $analysed->hasdata = !empty($analysed->individualdata);
        return $analysed;
    }

    public function get_display_value($item, $value, $forexport = false) {
        if (strval($value) === '') {
            return '';
        }
        return $item->presentation == self::MODE_RESPONSETIME ? userdate($value) : $value;
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
     * Calculates the value of the item (time, course, course category)
     *
     * @param stdClass $item
     * @param stdClass $feedback
     * @param int $courseid
     * @return string
     */
    protected function get_current_value($item, $feedback, $courseid) {
        global $DB;
        switch ($item->presentation) {
            case self::MODE_RESPONSETIME:
                if ($feedback->anonymous != FEEDBACK_ANONYMOUS_YES) {
                    // Response time is not allowed in anonymous feedbacks.
                    return time();
                }
                break;
            case self::MODE_COURSE:
                $course = get_course($courseid);
                return format_string($course->shortname, true,
                        array('context' => context_course::instance($course->id)));
                break;
            case self::MODE_CATEGORY:
                if ($courseid !== SITEID) {
                    $coursecategory = $DB->get_record_sql('SELECT cc.id, cc.name FROM {course_categories} cc, {course} c '
                            . 'WHERE c.category = cc.id AND c.id = ?', array($courseid));
                    return format_string($coursecategory->name, true,
                            array('context' => context_coursecat::instance($coursecategory->id)));
                }
                break;
        }
        return '';
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    public function complete_form_element($item, $form) {
        if ($form->get_mode() == mod_feedback_complete_form::MODE_VIEW_RESPONSE) {
            $value = strval($form->get_item_value($item));
        } else {
            $value = $this->get_current_value($item,
                    $form->get_feedback(), $form->get_current_course_id());
        }
        $printval = $this->get_display_value($item, $value);

        $class = '';
        switch ($item->presentation) {
            case self::MODE_RESPONSETIME:
                $class = 'info-responsetime';
                $value = $value ? self::CURRENTTIMESTAMP : '';
                break;
            case self::MODE_COURSE:
                $class = 'info-course';
                break;
            case self::MODE_CATEGORY:
                $class = 'info-category';
                break;
        }

        $name = $this->get_display_name($item);
        $inputname = $item->typ . '_' . $item->id;

        $element = $form->add_form_element($item,
                ['select', $inputname, $name,
                    array($value => $printval),
                    array('class' => $class)],
                false,
                false);
        $form->set_element_default($inputname, $value);
        $element->freeze();
        if ($form->get_mode() == mod_feedback_complete_form::MODE_COMPLETE) {
            $element->setPersistantFreeze(true);
        }
    }

    /**
     * Converts the value from complete_form data to the string value that is stored in the db.
     * @param mixed $value element from mod_feedback_complete_form::get_data() with the name $item->typ.'_'.$item->id
     * @return string
     */
    public function create_value($value) {
        if ($value === self::CURRENTTIMESTAMP) {
            return strval(time());
        }
        return parent::create_value($value);
    }

    public function can_switch_require() {
        return false;
    }

    public function get_data_for_external($item) {
        global $DB;
        $feedback = $DB->get_record('feedback', array('id' => $item->feedback), '*', MUST_EXIST);
        // Return the default value (course name, category name or timestamp).
        return $this->get_current_value($item, $feedback, $feedback->course);
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
        $data = $this->get_analysis($item, $groupid, $courseid);

        if (is_array($data->dataexternal)) {
            return array_map('json_encode', $data->dataexternal);
        }
        return $externaldata;
    }
}
