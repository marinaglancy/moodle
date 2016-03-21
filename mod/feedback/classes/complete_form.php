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
 * Contains class mod_feedback_complete_form
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_feedback_complete_form
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_complete_form extends moodleform {

    protected $feedback;
    protected $cm;
    protected $courseid;
    protected $gopage;
    protected $completedtmp;

    public function definition() {
        $this->feedback = $this->_customdata['feedback'];
        $this->cm = $this->_customdata['cm'];
        $this->courseid = $this->_customdata['courseid'];
        $this->gopage = $this->_customdata['gopage'];

        $mform = $this->_form;
        $mform->addElement('hidden', 'id', $this->cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $this->courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'gopage');
        $mform->setType('gopage', PARAM_INT);
        $mform->addElement('hidden', 'lastpage');
        $mform->setType('lastpage', PARAM_INT);
        $mform->addElement('hidden', 'startitempos');
        $mform->setType('startitempos', PARAM_INT);
        $mform->addElement('hidden', 'lastitempos');
        $mform->setType('lastitempos', PARAM_INT);

        if ($this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES) {
            $anonymousmodeinfo = get_string('anonymous', 'feedback');
        } else if ($this->feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
            $anonymousmodeinfo = get_string('non_anonymous', 'feedback');
        }
        if (isloggedin() && !isguestuser()) {
            $mform->addElement('static', 'anonymousmode', '', get_string('mode', 'feedback') . ': ' . $anonymousmodeinfo);
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'gopreviouspage', get_string('previous_page', 'feedback'));
        $buttonarray[] = &$mform->createElement('submit', 'gonextpage', get_string('next_page', 'feedback'));
        $buttonarray[] = &$mform->createElement('submit', 'savevalues', get_string('save_entries', 'feedback'),
                array('class' => 'form-submit'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $this->completedtmp = feedback_retrieve_response_tmp($this->feedback, $this->courseid);

        $this->set_data(array('gopage' => $this->_customdata['gopage']));
    }

    /*
    public function set_defaults() {
        // TODO this is dodgy
        global  $DB;
        $defaultvalues = array();
        if ($this->completedtmp) {
            $sql = "SELECT fi.id, fi.typ, fv.value
                       FROM {feedback_valuetmp} fv, {feedback_item} fi
                      WHERE fv.course_id = :courseid
                            AND fv.completed = :completedid
                            AND fv.item = fi.id";
            $params['completedid'] = $this->completedtmp->id;
            $params['courseid']    = $this->courseid;
            $params['feedbackid']  = $this->feedback->id;

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $record) {
                $defaultvalues[$record->typ . '_' . $record->id] = $record->value;
            }
            $rs->close();
        }
    }
     */

    //public function get_completedtmp_id() {
    //    return isset($this->completedtmp->id) ? $this->completedtmp->id : null;
    //}

    /**
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     */
    public function definition_after_data() {
        global $DB, $OUTPUT;
        $mform = $this->_form;
        parent::definition_after_data();
        list($startposition, $firstpagebreak, $ispagebreak, $feedbackitems) =
                feedback_get_page_boundaries($this->feedback, $this->gopage);

        // Add elements.
        $startitem = null;
        $lastbreakposition = 0;
        foreach ($feedbackitems as $feedbackitem) {
            if (!isset($startitem)) {
                // Avoid showing double pagebreaks.
                if ($feedbackitem->typ == 'pagebreak') {
                    continue;
                }
                $startitem = $feedbackitem;
            }

            if ($feedbackitem->dependitem > 0) {
                // Check if the conditions are ok.
                if (!isset($this->completedtmp->id) OR 
                        !feedback_compare_item_value($this->completedtmp->id,
                            $feedbackitem->dependitem, $feedbackitem->dependvalue, true)) {
                    $lastitem = $feedbackitem;
                    $lastbreakposition = $feedbackitem->position;
                    continue;
                }
            }

            if ($feedbackitem->typ != 'pagebreak') {
                $itemobj = feedback_get_item_class($feedbackitem->typ);
                $itemobj->complete_form_element($feedbackitem, $this);
            }

            $lastbreakposition = $feedbackitem->position; // Last item-pos (item or pagebreak).
            if ($feedbackitem->typ == 'pagebreak') {
                break;
            } else {
                $lastitem = $feedbackitem;
            }
        }

        // Remove invalid buttons (for example, no "previous page" if we are on the first page).
        $maxitemcount = $DB->count_records('feedback_item', array('feedback' => $this->feedback->id));
        if (!$ispagebreak || $lastbreakposition <= $firstpagebreak->position) {
            $this->remove_button('gopreviouspage');
        }
        if ($lastbreakposition >= $maxitemcount) {
            $this->remove_button('gonextpage');
        }
        if ($lastbreakposition < $maxitemcount) {
            $this->remove_button('savevalues');
        }

        // Move buttons to the end of the form.
        $mform->addElement('hidden', '__dummyelement');
        $buttons = $mform->removeElement('buttonar', false);
        $mform->insertElementBefore($buttons, '__dummyelement');
        $mform->removeElement('__dummyelement');
    }

    private function remove_button($buttonname) {
        $el = $this->_form->getElement('buttonar');
        foreach ($el->_elements as $idx => $button) {
            if ($button instanceof MoodleQuickForm_submit && $button->getName() === $buttonname) {
                unset($el->_elements[$idx]);
                return;
            }
        }
    }

    /**
     * Returns value for this element that is already stored in temporary table,
     * usually only available when user clicked "Previous page". Null means no value is stored.
     *
     * @param stdClass $feedbackitem
     * @return string
     */
    public function get_item_value($feedbackitem) {
                $value = null;
                //get the value
                //$frmvaluename = $feedbackitem->typ . '_'. $feedbackitem->id;
                /*if ($mform->getElementValue('id')) {
                    $value = $mform->getElementValue($frmvaluename);
                    $value = feedback_clean_input_value($feedbackitem, $value);
                } else {*/
                    if (isset($this->completedtmp->id)) {
                        $value = feedback_get_item_value($this->completedtmp->id,
                                                         $feedbackitem->id,
                                                         true);
                    }
                //}
        return $value;
    }

    /**
     *
     * @return MoodleQuickForm
     */
    public function get_quick_form() {
        return $this->_form;
    }

    public function get_course_id() {
        return $this->courseid;
    }

    public function get_feedback() {
        return $this->feedback;
    }

    /**
     *
     * @return cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    public function get_current_course_id() {
        if ($this->feedback->course == SITEID && $this->courseid) {
            return $this->courseid;
        }
        return $this->feedback->course;
    }
}
