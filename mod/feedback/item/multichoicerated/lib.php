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

define('FEEDBACK_RADIORATED_ADJUST_SEP', '<<<<<');

define('FEEDBACK_MULTICHOICERATED_MAXCOUNT', 10); //count of possible items
define('FEEDBACK_MULTICHOICERATED_VALUE_SEP', '####');
define('FEEDBACK_MULTICHOICERATED_VALUE_SEP2', '/');
define('FEEDBACK_MULTICHOICERATED_TYPE_SEP', '>>>>>');
define('FEEDBACK_MULTICHOICERATED_LINE_SEP', '|');
define('FEEDBACK_MULTICHOICERATED_ADJUST_SEP', '<<<<<');
define('FEEDBACK_MULTICHOICERATED_IGNOREEMPTY', 'i');
define('FEEDBACK_MULTICHOICERATED_HIDENOSELECT', 'h');

class feedback_item_multichoicerated extends feedback_item_base {
    protected $type = "multichoicerated";

    public function build_editform($item, $feedback, $cm) {
        global $DB, $CFG;
        require_once('multichoicerated_form.php');

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
        $info = $this->get_info($item);

        $item->ignoreempty = $this->ignoreempty($item);
        $item->hidenoselect = $this->hidenoselect($item);

        //all items for dependitem
        $feedbackitems = feedback_get_depend_candidates_for_item($feedback, $item);
        $commonparams = array('cmid'=>$cm->id,
                             'id'=>isset($item->id) ? $item->id : null,
                             'typ'=>$item->typ,
                             'items'=>$feedbackitems,
                             'feedback'=>$feedback->id);

        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position,
                            'info' => $info,
                            'nameoptions' => $this->get_name_editor_options($item));

        $this->item_form = new feedback_multichoicerated_form('edit_item.php', $customdata);
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

        $this->set_ignoreempty($item, $item->ignoreempty);
        $this->set_hidenoselect($item, $item->hidenoselect);

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
     * @param int $groupid
     * @param int $courseid
     * @param bool $forexport prepare for export or for display (for example: newlines should be converted to <br> for display but not for export)
     * @return stdClass
     */
    public function get_analysis($item, $groupid = false, $courseid = false, $forexport = false) {
        global $OUTPUT;
        $analyseditem = parent::get_analysis($item, $groupid, $courseid, $forexport);
        $analyseditem->data = [];

        //die moeglichen Antworten extrahieren
        $info = $this->get_info($item);
        $lines = null;
        $lines = explode (FEEDBACK_MULTICHOICERATED_LINE_SEP, $info->presentation);
        if (!is_array($lines)) {
            return $analyseditem;
        }

        //die Werte holen
        $values = feedback_get_group_values($item, $groupid, $courseid, $this->ignoreempty($item));
        if (!$values) {
            return $analyseditem;
        }

        $analyseditem->hasdata = true;

        $sizeoflines = count($lines);
        $totalanswercount = 0;
        $totalanswersvalues = 0;
        for ($i = 1; $i <= $sizeoflines; $i++) {
            $item_values = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $lines[$i-1]);
            $ans = new stdClass();
            $ans->answertext = $item_values[1];
            $avg = 0.0;
            $anscount = 0;
            foreach ($values as $value) {
                //ist die Antwort gleich dem index der Antworten + 1?
                if ($value->value == $i) {
                    $avg += $item_values[0]; //erst alle Werte aufsummieren
                    $totalanswersvalues += $item_values[0];
                    $anscount++;
                    $totalanswercount++;
                }
            }
            $ans->answercount = $anscount;
            $ans->avg = doubleval($avg) / doubleval(count($values));
            $ans->value = $item_values[0];
            $ans->quotient = $ans->answercount / count($values);
            $analyseditem->data[] = $ans;
        }
        if ($totalanswercount) {
            $analyseditem->avg = format_float($totalanswersvalues / $totalanswercount, 2, true, true);
        }

        // Prepare chart.
        $data = [];
        foreach ($analyseditem->data as $count => $val) {
            $quotient = format_float($val->quotient * 100, 2);

            if ($val->quotient > 0) {
                $strquotient = ' ('.$quotient.' %)';
            } else {
                $strquotient = '';
            }

            $data['labels'][$count] = strip_tags($this->get_display_value($item, $count+1, $forexport)); // Charts do not like html tags.
            $data['series'][$count] = $val->answercount;
            $data['series_labels'][$count] = $val->answercount . $strquotient;
        }
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $series = new \core\chart_series(format_string(get_string("responses", "feedback")), $data['series']);
        $series->set_labels($data['series_labels']);
        $chart->add_series($series);
        $chart->set_labels($data['labels']);

        $analyseditem->summary = $OUTPUT->render($chart);
        $analyseditem->summary .= html_writer::div(get_string('averageforanalysis', 'feedback', $analyseditem->avg), 'average');

        // "Sort by courses".
        if (!$forexport && ($analysisbycourse = $this->get_analysis_by_course($item))) {
            $analyseditem->summary .= $analysisbycourse;
        }

        return $analyseditem;
    }

    /**
     * Part of analysis summary - table "Average by course".
     *
     * @param stdClass $item
     * @return string
     */
    protected function get_analysis_by_course($item) {
        global $PAGE, $DB;
        // Find course where feedback module was created.
        if ($PAGE->cm && $PAGE->cm->instance == $item->feedback && $PAGE->cm->modname === 'feedback') {
            $courseid = $PAGE->cm->course;
        } else {
            $courseid = $DB->get_field('feedback', 'course', ['id' => $item->feedback]);
        }
        if (!$courseid || $courseid !== SITEID) {
            // No by course analysis for feedbacks that are not on the front page.
            return '';
        }

        $valuereal = $DB->sql_cast_char2real('fv.value', true);
        $sql = "SELECT c.id, c.shortname, c.fullname, c.idnumber, 
            SUM($valuereal) AS sumvalue, COUNT(fv.value) as countvalue
            FROM {feedback_value} fv, {course} c, {feedback_item} fi
            WHERE fv.course_id = c.id AND fi.id = fv.item AND fi.typ = ? AND fv.item = ?
            GROUP BY c.id, c.shortname, c.fullname, c.idnumber
            ORDER BY sumvalue desc";

        $rv = '';
        $courses = $DB->get_records_sql($sql, array($item->typ, $item->id));
        if (count($courses) > 1) {
            $rv .= '<table class="averagebycourse">';
            $rv .= "<tr><th colspan=\"2\">".get_string('averagebycourse', 'feedback')."</th></tr>";
            foreach ($courses as $c) {
                $coursecontext = context_course::instance($c->id);
                $shortname = format_string(get_course_display_name_for_list($c), true, $coursecontext);
                $value = format_float(($c->sumvalue / $c->countvalue), 2, true, true);;
                $rv .= "<tr><td>$shortname</td><td align=\"right\">$value</td></tr>";
            }
            $rv .= '</table>';
        }
        return $rv;
    }

    public function get_display_value($item, $value, $forexport = false) {
        $options = $this->get_options($item);
        if (is_number($value) && array_key_exists($value, $options)) {
            return $forexport ? strip_tags($options[$value]) : $options[$value];
        }
        return '';
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false) {

        $analyseditem = $this->get_analysis($item, $groupid, $courseid, true);

        $data = $analyseditem->data;

        //write the item
        $worksheet->write_string($row_offset, 0, $analyseditem->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $analyseditem->shortname, $xls_formats->head2);
        if (is_array($data)) {
            $sizeofdata = count($data);
            for ($i = 0; $i < $sizeofdata; $i++) {
                $analysed_data = $data[$i];

                $worksheet->write_string($row_offset,
                                $i + 2,
                                $analysed_data->answertext,
                                $xls_formats->value_bold);

                $worksheet->write_number($row_offset + 1,
                                $i + 2,
                                $analysed_data->answercount,
                                $xls_formats->default);

            }
            //mittelwert anzeigen
            if (isset($analyseditem->avg)) {
                $worksheet->write_string($row_offset,
                                count($data) + 2,
                                get_string('average', 'feedback'),
                                $xls_formats->value_bold);

                $worksheet->write_number($row_offset + 1,
                                count($data) + 2,
                                $analyseditem->avg,
                                $xls_formats->value_bold);
            }
        }
        $row_offset +=2;
        return $row_offset;
    }

    /**
     * Options for the multichoice element
     * @param stdClass $item
     * @return array
     */
    protected function get_options($item) {
        $info = $this->get_info($item);
        $lines = explode(FEEDBACK_MULTICHOICERATED_LINE_SEP, $info->presentation);
        $options = array();
        foreach ($lines as $idx => $line) {
            list($weight, $optiontext) = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $line);
            $options[$idx + 1] = format_text("<span class=\"weight\">($weight) </span>".$optiontext,
                    FORMAT_HTML, array('noclean' => true, 'para' => false));
        }
        if ($info->subtype === 'r' && !$this->hidenoselect($item)) {
            $options = array(0 => get_string('not_selected', 'feedback')) + $options;
        }

        return $options;
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    public function complete_form_element($item, $form) {
        $info = $this->get_info($item);
        $name = $this->get_display_name($item);
        $class = 'multichoicerated-' . $info->subtype;
        $inputname = $item->typ . '_' . $item->id;
        $options = $this->get_options($item);
        if ($info->subtype === 'd' || $form->is_frozen()) {
            $el = $form->add_form_element($item,
                    ['select', $inputname, $name, array('' => '') + $options, array('class' => $class)]);
        } else {
            $objs = array();
            if (!array_key_exists(0, $options)) {
                // Always add '0' as hidden element, otherwise form submit data may not have this element.
                $objs[] = ['hidden', $inputname];
            }
            foreach ($options as $idx => $label) {
                $objs[] = ['radio', $inputname, '', $label, $idx];
            }
            // Span to hold the element id. The id is used for drag and drop reordering.
            $objs[] = ['static', '', '', html_writer::span('', '', ['id' => 'feedback_item_' . $item->id])];
            $separator = $info->horizontal ? ' ' : '<br>';
            $class .= ' multichoicerated-' . ($info->horizontal ? 'horizontal' : 'vertical');
            $el = $form->add_form_group_element($item, 'group_'.$inputname, $name, $objs, $separator, $class);
            $form->set_element_type($inputname, PARAM_INT);

            // Set previously input values.
            $form->set_element_default($inputname, $form->get_item_value($item));

            // Process "required" rule.
            if ($item->required) {
                $form->add_validation_rule(function($values, $files) use ($item) {
                    $inputname = $item->typ . '_' . $item->id;
                    return empty($values[$inputname]) ? array('group_' . $inputname => get_string('required')) : true;
                });
            }
        }
    }

    /**
     * Compares the dbvalue with the dependvalue
     *
     * @param stdClass $item
     * @param string $dbvalue is the value input by user in the format as it is stored in the db
     * @param string $dependvalue is the value that it needs to be compared against
     */
    public function compare_value($item, $dbvalue, $dependvalue) {

        if (is_array($dbvalue)) {
            $dbvalues = $dbvalue;
        } else {
            $dbvalues = explode(FEEDBACK_MULTICHOICERATED_LINE_SEP, $dbvalue);
        }

        $info = $this->get_info($item);
        $presentation = explode (FEEDBACK_MULTICHOICERATED_LINE_SEP, $info->presentation);
        $index = 1;
        foreach ($presentation as $pres) {
            $presvalues = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $pres);

            foreach ($dbvalues as $dbval) {
                if ($dbval == $index AND trim($presvalues[1]) == $dependvalue) {
                    return true;
                }
            }
            $index++;
        }
        return false;
    }

    public function get_info($item) {
        $presentation = empty($item->presentation) ? '' : $item->presentation;

        $info = new stdClass();
        //check the subtype of the multichoice
        //it can be check(c), radio(r) or dropdown(d)
        $info->subtype = '';
        $info->presentation = '';
        $info->horizontal = false;

        $parts = explode(FEEDBACK_MULTICHOICERATED_TYPE_SEP, $item->presentation);
        @list($info->subtype, $info->presentation) = $parts;

        if (!isset($info->subtype)) {
            $info->subtype = 'r';
        }

        if ($info->subtype != 'd') {
            $parts = explode(FEEDBACK_MULTICHOICERATED_ADJUST_SEP, $info->presentation);
            @list($info->presentation, $info->horizontal) = $parts;

            if (isset($info->horizontal) AND $info->horizontal == 1) {
                $info->horizontal = true;
            } else {
                $info->horizontal = false;
            }
        }

        $info->values = $this->prepare_presentation_values_print($info->presentation,
                                                    FEEDBACK_MULTICHOICERATED_VALUE_SEP,
                                                    FEEDBACK_MULTICHOICERATED_VALUE_SEP2);
        return $info;
    }

    public function prepare_presentation_values($linesep1,
                                         $linesep2,
                                         $valuestring,
                                         $valuesep1,
                                         $valuesep2) {

        $lines = explode($linesep1, $valuestring);
        $newlines = array();
        foreach ($lines as $line) {
            $value = '';
            $text = '';
            if (strpos($line, $valuesep1) === false) {
                $value = 0;
                $text = $line;
            } else {
                @list($value, $text) = explode($valuesep1, $line, 2);
            }

            $value = intval($value);
            $newlines[] = $value.$valuesep2.$text;
        }
        $newlines = implode($linesep2, $newlines);
        return $newlines;
    }

    public function prepare_presentation_values_print($valuestring, $valuesep1, $valuesep2) {
        $valuestring = str_replace(array("\n","\r"), "", $valuestring);
        return $this->prepare_presentation_values(FEEDBACK_MULTICHOICERATED_LINE_SEP,
                                                  "\n",
                                                  $valuestring,
                                                  $valuesep1,
                                                  $valuesep2);
    }

    public function prepare_presentation_values_save($valuestring, $valuesep1, $valuesep2) {
        $valuestring = str_replace("\r", "\n", $valuestring);
        $valuestring = str_replace("\n\n", "\n", $valuestring);
        return $this->prepare_presentation_values("\n",
                        FEEDBACK_MULTICHOICERATED_LINE_SEP,
                        $valuestring,
                        $valuesep1,
                        $valuesep2);
    }

    public function set_ignoreempty($item, $ignoreempty=true) {
        $item->options = str_replace(FEEDBACK_MULTICHOICERATED_IGNOREEMPTY, '', $item->options);
        if ($ignoreempty) {
            $item->options .= FEEDBACK_MULTICHOICERATED_IGNOREEMPTY;
        }
    }

    public function ignoreempty($item) {
        if (strstr($item->options, FEEDBACK_MULTICHOICERATED_IGNOREEMPTY)) {
            return true;
        }
        return false;
    }

    public function set_hidenoselect($item, $hidenoselect=true) {
        $item->options = str_replace(FEEDBACK_MULTICHOICERATED_HIDENOSELECT, '', $item->options);
        if ($hidenoselect) {
            $item->options .= FEEDBACK_MULTICHOICERATED_HIDENOSELECT;
        }
    }

    public function hidenoselect($item) {
        if (strstr($item->options, FEEDBACK_MULTICHOICERATED_HIDENOSELECT)) {
            return true;
        }
        return false;
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

        if (!empty($data->data) && is_array($data->data)) {
            foreach ($data->data as $d) {
                $externaldata[] = json_encode($d);
            }
        }
        return $externaldata;
    }
}
