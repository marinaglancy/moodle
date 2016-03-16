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
 * Contains class mod_feedback_completion_page
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_feedback_completion_page
 *
 * @package   local_ma
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_completion_page {
    protected $feedback;
    protected $cm;
    protected $gopage;

    protected $feedbackitems;
    protected $feedbackcompletedtmp;
    protected $formdata;
    protected $firstpagebreak;
    protected $ispagebreak;
    protected $startposition;


    public function __construct($feedback, $cm, $gopage) {
        $this->feedback = $feedback;
        $this->cm = $cm;
        $this->gopage = $gopage;
        $this->formdata = data_submitted();
    }

    public function prepare() {
        global $DB;

        $feedback = $this->feedback;
        $gopage = $this->gopage;

        if ($allbreaks = feedback_get_all_break_positions($feedback->id)) {
            if ($gopage <= 0) {
                $startposition = 0;
            } else {
                if (!isset($allbreaks[$gopage - 1])) {
                    $gopage = count($allbreaks);
                }
                $startposition = $allbreaks[$gopage - 1];
            }
            $ispagebreak = true;
        } else {
            $startposition = 0;
            //$newpage = 0;
            $ispagebreak = false;
        }

        //get the feedbackitems after the last shown pagebreak
        $select = 'feedback = ? AND position > ?';
        $params = array($feedback->id, $startposition);
        $feedbackitems = $DB->get_records_select('feedback_item', $select, $params, 'position');

        //get the first pagebreak
        $params = array('feedback' => $feedback->id, 'typ' => 'pagebreak');
        if ($pagebreaks = $DB->get_records('feedback_item', $params, 'position')) {
            $pagebreaks = array_values($pagebreaks);
            $firstpagebreak = $pagebreaks[0];
        } else {
            $firstpagebreak = false;
        }


        $this->startposition = $startposition;
        $this->ispagebreak = $ispagebreak;
        $this->firstpagebreak = $firstpagebreak;
        $this->feedbackitems = $feedbackitems;
    }

    public function prepare_completed(&$savereturn) {
        global $SESSION;

        $feedback = $this->feedback;

        //get the values of completeds before done. Anonymous user can not get these values.
        if ((!isset($SESSION->feedback->is_started)) AND
                              (!isset($savereturn)) AND
                              ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO)) {

            $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $feedback->course);
            if (!$feedbackcompletedtmp) {
                $feedbackcompleted = feedback_get_current_completed($feedback->id, false, $feedback->course);
                if ($feedbackcompleted) {
                    //copy the values to feedback_valuetmp create a completedtmp
                    $feedbackcompletedtmp = feedback_set_tmp_values($feedbackcompleted);
                }
            }
        } else if (isloggedin() && !isguestuser()) {
            $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $feedback->course);
        } else {
            $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $feedback->course, sesskey());
        }

        $this->feedbackcompletedtmp = $feedbackcompletedtmp;
    }

    public function display(&$savereturn) {
        global $OUTPUT, $DB, $CFG, $SESSION;

        $this->prepare();
        $this->prepare_completed($savereturn);

        $feedback = $this->feedback;
        $cm = $this->cm;
        $gopage = $this->gopage;

        $feedbackitems = $this->feedbackitems;
        $feedbackcompletedtmp = $this->feedbackcompletedtmp;
        $formdata = $this->formdata;
        $firstpagebreak = $this->firstpagebreak;
        $ispagebreak = $this->ispagebreak;
        $startposition = $this->startposition;

        if (!$feedbackitems) {
            return;
        }


        $highlightrequired = false;
        $maxitemcount = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));

            echo $OUTPUT->box_start('feedback_form');
            echo '<form action="complete.php" class="feedback_complete" method="post">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $anonymousmodeinfo = '';
            switch ($feedback->anonymous) {
                case FEEDBACK_ANONYMOUS_YES:
                    echo '<input type="hidden" name="anonymous" value="1" />';
                    $inputvalue = 'value="'.FEEDBACK_ANONYMOUS_YES.'"';
                    echo '<input type="hidden" name="anonymous_response" '.$inputvalue.' />';
                    $anonymousmodeinfo = get_string('anonymous', 'feedback');
                    break;
                case FEEDBACK_ANONYMOUS_NO:
                    echo '<input type="hidden" name="anonymous" value="0" />';
                    $inputvalue = 'value="'.FEEDBACK_ANONYMOUS_NO.'"';
                    echo '<input type="hidden" name="anonymous_response" '.$inputvalue.' />';
                    $anonymousmodeinfo = get_string('non_anonymous', 'feedback');
                    break;
            }
            if (isloggedin() && !isguestuser()) {
                echo $OUTPUT->box(get_string('mode', 'feedback') . ': ' . $anonymousmodeinfo, 'feedback_anonymousinfo');
            }
            //check, if there exists required-elements
            $params = array('feedback' => $feedback->id, 'required' => 1);
            $countreq = $DB->count_records('feedback_item', $params);
            if ($countreq > 0) {
                echo '<span class="fdescription required">';
                echo get_string('somefieldsrequired', 'form', '<img alt="'.get_string('requiredelement', 'form').
                    '" src="'.$OUTPUT->pix_url('req') .'" class="req" />');
                echo '</span>';
            }
            echo $OUTPUT->box_start('feedback_items');

            $select = 'feedback = ? AND hasvalue = 1 AND position < ?';
            $params = array($feedback->id, $startposition);
            $itemnr = $DB->count_records_select('feedback_item', $select, $params);
            $lastbreakposition = 0;
            $align = right_to_left() ? 'right' : 'left';

            foreach ($feedbackitems as $feedbackitem) {
                if (!isset($startitem)) {
                    //avoid showing double pagebreaks
                    if ($feedbackitem->typ == 'pagebreak') {
                        continue;
                    }
                    $startitem = $feedbackitem;
                }

                if ($feedbackitem->dependitem > 0) {
                    //chech if the conditions are ok
                    $fb_compare_value = feedback_compare_item_value($feedbackcompletedtmp->id,
                                                                    $feedbackitem->dependitem,
                                                                    $feedbackitem->dependvalue,
                                                                    true);
                    if (!isset($feedbackcompletedtmp->id) OR !$fb_compare_value) {
                        $lastitem = $feedbackitem;
                        $lastbreakposition = $feedbackitem->position;
                        continue;
                    }
                }

                if ($feedbackitem->dependitem > 0) {
                    $dependstyle = ' feedback_complete_depend';
                } else {
                    $dependstyle = '';
                }

                echo $OUTPUT->box_start('feedback_item_box_'.$align.$dependstyle);
                $value = '';
                //get the value
                $frmvaluename = $feedbackitem->typ . '_'. $feedbackitem->id;
                if (isset($savereturn)) {
                    $value = isset($formdata->{$frmvaluename}) ? $formdata->{$frmvaluename} : null;
                    $value = feedback_clean_input_value($feedbackitem, $value);
                } else {
                    if (isset($feedbackcompletedtmp->id)) {
                        $value = feedback_get_item_value($feedbackcompletedtmp->id,
                                                         $feedbackitem->id,
                                                         true);
                    }
                }
                if ($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
                    $itemnr++;
                    echo $OUTPUT->box_start('feedback_item_number_'.$align);
                    echo $itemnr;
                    echo $OUTPUT->box_end();
                }
                if ($feedbackitem->typ != 'pagebreak') {
                    echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
                    feedback_print_item_complete($feedbackitem, $value, $highlightrequired);
                    echo $OUTPUT->box_end();
                }

                echo $OUTPUT->box_end();

                $lastbreakposition = $feedbackitem->position; //last item-pos (item or pagebreak)
                if ($feedbackitem->typ == 'pagebreak') {
                    break;
                } else {
                    $lastitem = $feedbackitem;
                }
            }
            echo $OUTPUT->box_end();
            echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
            echo '<input type="hidden" name="feedbackid" value="'.$feedback->id.'" />';
            echo '<input type="hidden" name="lastpage" value="'.$gopage.'" />';
            if (isset($feedbackcompletedtmp->id)) {
                $inputvalue = 'value="'.$feedbackcompletedtmp->id.'"';
            } else {
                $inputvalue = 'value=""';
            }
            echo '<input type="hidden" name="completedid" '.$inputvalue.' />';
            echo '<input type="hidden" name="courseid" value="'. $feedback->course . '" />';
            echo '<input type="hidden" name="preservevalues" value="1" />';
            if (isset($startitem)) {
                echo '<input type="hidden" name="startitempos" value="'.$startitem->position.'" />';
                echo '<input type="hidden" name="lastitempos" value="'.$lastitem->position.'" />';
            }

            if ( $ispagebreak AND $lastbreakposition > $firstpagebreak->position) {
                $inputvalue = 'value="'.get_string('previous_page', 'feedback').'"';
                echo '<input name="gopreviouspage" type="submit" '.$inputvalue.' />';
            }
            if ($lastbreakposition < $maxitemcount) {
                $inputvalue = 'value="'.get_string('next_page', 'feedback').'"';
                echo '<input name="gonextpage" type="submit" '.$inputvalue.' />';
            }
            if ($lastbreakposition >= $maxitemcount) { //last page
                $inputvalue = 'value="'.get_string('save_entries', 'feedback').'"';
                echo '<input name="savevalues" type="submit" '.$inputvalue.' />';
            }

            echo '</form>';
            echo $OUTPUT->box_end();

            echo $OUTPUT->box_start('feedback_complete_cancel');
            if ($feedback->course) {
                $action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$feedback->course.'"';
            } else {
                if ($feedback->course == SITEID) {
                    $action = 'action="'.$CFG->wwwroot.'"';
                } else {
                    $action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$feedback->course.'"';
                }
            }
            echo '<form '.$action.' method="post" onsubmit=" ">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="courseid" value="'. $feedback->course . '" />';
            echo '<button type="submit">'.get_string('cancel').'</button>';
            echo '</form>';
            echo $OUTPUT->box_end();

            $SESSION->feedback->is_started = true;
    }
}
