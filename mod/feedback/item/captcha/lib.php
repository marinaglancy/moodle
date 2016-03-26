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

class feedback_item_captcha extends feedback_item_base {
    protected $type = "captcha";
    private $commonparams;
    private $item_form = false;
    private $item = false;
    private $feedback = false;

    public function init() {

    }

    public function build_editform($item, $feedback, $cm) {
        global $DB;

        $editurl = new moodle_url('/mod/feedback/edit.php', array('id'=>$cm->id));

        //ther are no settings for recaptcha
        if (isset($item->id) AND $item->id > 0) {
            notice(get_string('there_are_no_settings_for_recaptcha', 'feedback'), $editurl->out());
            exit;
        }

        //only one recaptcha can be in a feedback
        $params = array('feedback' => $feedback->id, 'typ' => $this->type);
        if ($DB->record_exists('feedback_item', $params)) {
            notice(get_string('only_one_captcha_allowed', 'feedback'), $editurl->out());
            exit;
        }

        $this->item = $item;
        $this->feedback = $feedback;
        $this->item_form = true; //dummy

        $lastposition = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));

        $this->item->feedback = $feedback->id;
        $this->item->template = 0;
        $this->item->name = get_string('captcha', 'feedback');
        $this->item->label = '';
        $this->item->presentation = '';
        $this->item->typ = $this->type;
        $this->item->hasvalue = $this->get_hasvalue();
        $this->item->position = $lastposition + 1;
        $this->item->required = 1;
        $this->item->dependitem = 0;
        $this->item->dependvalue = '';
        $this->item->options = '';
    }

    public function show_editform() {
    }

    public function is_cancelled() {
        return false;
    }

    public function get_data() {
        return true;
    }

    public function save_item() {
        global $DB;

        if (!$this->item) {
            return false;
        }

        if (empty($this->item->id)) {
            $this->item->id = $DB->insert_record('feedback_item', $this->item);
        } else {
            $DB->update_record('feedback_item', $this->item);
        }

        return $DB->get_record('feedback_item', array('id'=>$this->item->id));
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    public function get_analysed($item, $groupid = false, $courseid = false) {
        return null;
    }

    public function get_printval($item, $value) {
        return '';
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        return $itemnr;
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false) {
        return $row_offset;
    }

    public function get_display_name($item, $withpostfix = true) {
        return get_string('captcha', 'feedback');
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    public function complete_form_element($item, $form) {
        global $OUTPUT;
        $name = $this->get_display_name($item);
        $inputname = $item->typ . '_' . $item->id;

        if ($form->get_mode() != mod_feedback_complete_form::MODE_COMPLETE) {
            $form->add_form_element($item,
                    ['static', $inputname, $name],
                    false,
                    false);
        } else {
            $form->add_form_element($item,
                    ['recaptcha', $inputname, $name],
                    false,
                    false);
        }

        // Add recaptcha validation to the form.
        $form->add_validation_rule(function($values, $files) use ($item, $form) {
            $elementname = $item->typ . '_' . $item->id;
            $recaptchaelement = $form->get_form_element($elementname);
            if (empty($values['recaptcha_response_field'])) {
                return array($elementname => get_string('required'));
            } else if (!empty($values['recaptcha_challenge_field'])) {
                $challengefield = $values['recaptcha_challenge_field'];
                $responsefield = $values['recaptcha_response_field'];
                if (true !== ($result = $recaptchaelement->verify($challengefield, $responsefield))) {
                    return array($elementname => $result);
                }
            } else {
                return array($elementname => get_string('missingrecaptchachallengefield'));
            }
            return true;
        });

    }

    public function create_value($data) {
        global $USER;
        return $USER->sesskey;
    }

    //compares the dbvalue with the dependvalue
    //dbvalue is value stored in the db
    //dependvalue is the value to check
    public function compare_value($item, $dbvalue, $dependvalue) {
        if ($dbvalue == $dependvalue) {
            return true;
        }
        return false;
    }

    public function get_presentation($data) {
        return '';
    }

    public function get_hasvalue() {
        global $CFG;

        //is recaptcha configured in moodle?
        if (empty($CFG->recaptchaprivatekey) OR empty($CFG->recaptchapublickey)) {
            return 0;
        }
        return 1;
    }

    public function can_switch_require() {
        return false;
    }

    public function value_type() {
        return PARAM_RAW;
    }

    public function clean_input_value($value) {
        return clean_param($value, $this->value_type());
    }

    public function edit_actions($item, $feedback, $cm) {
        $actions = parent::edit_actions($item, $feedback, $cm);
        unset($actions['update']);
        return $actions;
    }
}
