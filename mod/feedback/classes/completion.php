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
 * Contains class mod_feedback_completion
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Collects information and methods about feedback completion (either complete.php or show_entries.php)
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_completion extends mod_feedback_structure {
    /** @var stdClass */
    protected $completed;
    /** @var stdClass */
    protected $completedtmp = null;
    /** @var stdClass[] */
    protected $valuestmp = null;
    /** @var stdClass[] */
    protected $values = null;

    protected $iscompleted = false;


    /**
     * Constructor
     *
     * @param stdClass $feedback feedback object, in case of the template
     *     this is the current feedback the template is accessed from
     * @param cm_info $cm course module object corresponding to the $feedback
     * @param int $courseid current course (for site feedbacks only)
     * @param bool $iscompleted has feedback been already completed? If yes either completedid or userid must be specified.
     * @param int $completedid id in the table feedback_completed, may be omitted if userid is specified
     *     but it is highly recommended because the same user may have multiple responses to the same feedback
     *     for different courses
     * @param int $userid id of the user - if specified only non-anonymous replies will be returned. If not
     *     specified only anonymous replies will be returned and the $completedid is mandatory.
     */
    public function __construct($feedback, $cm, $courseid, $iscompleted = false, $completedid = null, $userid = null) {
        global $DB;
        parent::__construct($feedback, $cm, $courseid, 0);
        if ($iscompleted) {
            // Retrieve information about the completion.
            $this->iscompleted = true;
            $params = array('feedback' => $feedback->id);
            if (!$userid && !$completedid) {
                throw new coding_exception('Either $completedid or $userid must be specified for completed feedbacks');
            }
            if ($completedid) {
                $params['id'] = $completedid;
            }
            if ($userid) {
                // We must respect the anonymousity of the reply that the user saw when they were completing the feedback,
                // not the current state that may have been changed later by the teacher.
                $params['anonymous_response'] = FEEDBACK_ANONYMOUS_NO;
                $params['userid'] = $userid;
            }
            $this->completed = $DB->get_record('feedback_completed', $params, '*', MUST_EXIST);
            $this->courseid = $this->completed->courseid;
        }
    }

    public function get_completed() {
        return $this->completed;
    }

    /**
     * Returns the temporary completion record for the current user or guest session
     *
     * @return stdClass|false record from feedback_completedtmp or false if not found
     */
    public function get_current_completed_tmp() {
        global $USER, $DB;
        if ($this->completedtmp === null) {
            $params = array('feedback' => $this->get_feedback()->id);
            if ($courseid = $this->get_courseid()) {
                $params['courseid'] = $courseid;
            }
            if (isloggedin() && !isguestuser()) {
                $params['userid'] = $USER->id;
            } else {
                $params['guestid'] = sesskey();
            }
            $this->completedtmp = $DB->get_record('feedback_completedtmp', $params);
        }
        return $this->completedtmp;
    }

    /**
     * compares the value of the itemid related to the completedid with the dependvalue.
     * this is used if a depend item is set.
     * the value can come as temporary or as permanently value. the deciding is done by $tmp.
     *
     * @global object
     * @global object
     * @param int $completedid
     * @param stdClass|int $item
     * @param mixed $dependvalue
     * @param boolean $tmp
     * @return bool
     */
    function compare_item_value($completedid, $item, $dependvalue, $tmp = false, $values = null) {
        global $DB;

        // TODO optimise

        if (is_int($item)) {
            $item = $DB->get_record('feedback_item', array('id' => $item));
        }

        $dbvalue = feedback_get_item_value($completedid, $item->id, $tmp);

        $itemobj = feedback_get_item_class($item->typ);
        return $itemobj->compare_value($item, $dbvalue, $dependvalue); //true or false
    }

    public function can_see_item($item) {
        if (empty($item->dependitem)) {
            return true;
        }
        $allitems = $this->get_items();
        // TODO check that dependitem is BEFORE $item and there is a pagebreak between them.
        if (isset($allitems[$item->dependitem])) {
            $completedtmp = $this->get_current_completed_tmp();
            // Check if the conditions are ok.
            if (!$completedtmp OR
                    !$this->compare_item_value($this->completedtmp->id,
                        $allitems[$item->dependitem], $item->dependvalue, true)) { // TODO compare against stored items!
                return false;
            }
        }
        return true;
    }

    public function get_values_tmp($item = null) {
        global $DB;
        if ($this->valuestmp === null) {
            $completedtmp = $this->get_current_completed_tmp();
            if ($completedtmp) {
                $this->valuestmp = $DB->get_records_menu('feedback_valuetmp',
                        ['completed' => $completedtmp->id], '', 'item, value');
            } else {
                $this->valuestmp = array();
            }
        }
        if ($item) {
            return array_key_exists($item->id, $this->valuestmp) ? $this->valuestmp[$item->id] : null;
        }
        return $this->valuestmp;
    }

    public function get_values($item = null) {
        global $DB;
        if ($this->values === null) {
            if ($this->completed) {
                $this->values = $DB->get_records_menu('feedback_value',
                        ['completed' => $this->completed->id], '', 'item, value');
            } else {
                $this->values = array();
            }
        }
        if ($item) {
            return array_key_exists($item->id, $this->values) ? $this->values[$item->id] : null;
        }
        return $this->values;
    }

    public function get_pages() {
        $pages = [[]]; // The first page always exists.
        $items = $this->get_items();
        foreach ($items as $item) {
            if ($item->typ === 'pagebreak') {
                $pages[] = [];
            } else if ($this->can_see_item($item) !== false) {
                $pages[count($pages) - 1][] = $item;
            }
        }
        return $pages;
    }

    /**
     * Returns the last page that has items with the value (i.e. not label) which have not been answered.
     * @return int|null page index (0-based) or null if the first page has unanswered items.
     */
    public function get_last_completed_page() {
        $completed = [];
        $incompleted = [];
        $pages = $this->get_pages();
        foreach ($pages as $pageidx => $pageitems) {
            foreach ($pageitems as $item) {
                if ($item->hasvalue) {
                    if ($this->get_values_tmp($item) !== null) {
                        $completed[$pageidx] = true;
                    } else {
                        $incompleted[$pageidx] = true;
                    }
                }
            }
        }
        $completed = array_keys($completed);
        $incompleted = array_keys($incompleted);
        // If some page has both completed and incompleted items it is considered incompleted.
        $completed = array_diff($completed, $incompleted);
        // If the completed page follows an incompleted page, it does not count.
        $firstincompleted = $incompleted ? min($incompleted) : null;
        if ($firstincompleted !== null) {
            $completed = array_filter($completed, function($a) use ($firstincompleted) {
                return $a < $firstincompleted;
            });
        }
        $lastcompleted = $completed ? max($completed) : null;
        return [$lastcompleted, $firstincompleted];
    }

    /**
     *
     * @param type $gopage
     */
    public function guess_next_page($gopage) {

    }

    public function get_next_page($gopage, $strictcheck = true) {
        if ($strictcheck) {
            list($lastcompleted, $firstincompleted) = $this->get_last_completed_page();
            if ($firstincompleted !== null && $firstincompleted <= $gopage) {
                return $firstincompleted;
            }
        }
        $pages = $this->get_pages();
        for ($pageidx = $gopage + 1; $pageidx < count($pages); $pageidx++) {
            if (!empty($pages[$pageidx])) {
                return $pageidx;
            }
        }
        // No further pages in the feedback have any visible items.
        return null;
    }

    public function get_previous_page($gopage, $strictcheck = true) {
        if (!$gopage) {
            return null;
        }
        // TODO strict check
        /*if ($strictcheck) {
            list($lastcompleted, $firstincompleted) = $this->get_last_completed_page();
            if ($firstincompleted !== null && $firstincompleted <= $gopage) {
                return $firstincompleted;
            }
        }*/
        $pages = $this->get_pages();
        for ($pageidx = $gopage - 1; $pageidx >= 0; $pageidx--) {
            if (!empty($pages[$pageidx])) {
                return $pageidx;
            }
        }
        // We are already on the first page that has items.
        return null;
    }

    /**
     * Page index to resume the feedback
     *
     * When user abandones answering feedback and then comes back to it we should send him
     * to the first page after the last page he fully completed.
     * @return int
     */
    public function get_resume_page() {
        list($lastcompleted, $firstincompleted) = $this->get_last_completed_page();
        return $lastcompleted === null ? 0 : $this->get_next_page($lastcompleted, false);
    }

    /**
     *
     * @param stdClass $feedback
     * @param int $gopage
     * @return array [$startposition, $firstpagebreak, $ispagebreak, $feedbackitems]
     */
    function feedback_get_page_boundaries($gopage) {
        global $DB;
        $feedback = $this->get_feedback();
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
        return array($startposition, $firstpagebreak, $ispagebreak, $feedbackitems);
    }

    public function get_items_on_page($gopage) {
        global $DB, $OUTPUT;
        $feedback = $this->get_feedback();
        list($startposition, $firstpagebreak, $ispagebreak, $feedbackitems) =
                $this->feedback_get_page_boundaries($gopage);

        // Add elements.
        $items = array();
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

            if (!$this->can_see_item($feedbackitem)) {
                $lastitem = $feedbackitem;
                $lastbreakposition = $feedbackitem->position;
                continue;
            }

            if ($feedbackitem->typ != 'pagebreak') {
                $items[] = $feedbackitem;
            }

            $lastbreakposition = $feedbackitem->position; // Last item-pos (item or pagebreak).
            if ($feedbackitem->typ == 'pagebreak') {
                break;
            } else {
                $lastitem = $feedbackitem;
            }
        }
        return $items;
    }

    /**
     * Creates a new record in the 'feedback_completedtmp' table for the current user/guest session
     *
     * @param stdClass $feedback record from db table 'feedback'
     * @param int $courseid current course (only for site feedbacks)
     * @return stdClass record from feedback_completedtmp or false if not found
     */
    protected function create_current_completed_tmp() {
        global $USER, $DB;
        $record = (object)['feedback' => $this->feedback->id];
        if ($this->get_courseid()) {
            $record->courseid = $this->get_courseid();
        }
        if (isloggedin() && !isguestuser()) {
            $record->userid = $USER->id;
        } else {
            $record->guestid = sesskey();
        }
        $record->timemodified = time();
        $record->anonymous_response = $this->feedback->anonymous;
        $id = $DB->insert_record('feedback_completedtmp', $record);
        $this->completedtmp = $DB->get_record('feedback_completedtmp', ['id' => $id]);
        return $this->completedtmp;
    }

    public function save_response_tmp($data) {
        global $DB;
        if (!$completedtmp = $this->get_current_completed_tmp()) {
            $completedtmp = $this->create_current_completed_tmp();
        } else {
            $currentime = time();
            $DB->update_record('feedback_completedtmp',
                    ['id' => $completedtmp->id, 'timemodified' => $currentime]);
            $completedtmp->timemodified = $currentime;
        }

        // Find all existing values.
        $existingvalues = $DB->get_records_menu('feedback_valuetmp',
                ['completed' => $completedtmp->id], '', 'item, id');

        // Loop through all feedback items and save the ones that are present in $data.
        $allitems = $this->get_items();
        foreach ($allitems as $item) {
            if (!$item->hasvalue) {
                continue;
            }
            $keyname = $item->typ . '_' . $item->id;
            if (!isset($data->$keyname)) {
                // This item is either on another page or dependency was not met - nothing to save.
                continue;
            }

            $newvalue = ['item' => $item->id, 'completed' => $completedtmp->id, 'course_id' => $completedtmp->courseid];

            // Convert the value to string that can be stored in 'feedback_valuetmp' or 'feedback_value'.
            $itemobj = feedback_get_item_class($item->typ);
            $newvalue['value'] = $itemobj->create_value($data->$keyname);

            // Update or insert the value in the 'feedback_valuetmp' table.
            if (array_key_exists($item->id, $existingvalues)) {
                $newvalue['id'] = $existingvalues[$item->id];
                $DB->update_record('feedback_valuetmp', $newvalue);
            } else {
                $DB->insert_record('feedback_valuetmp', $newvalue);
            }
        }

        // Reset valuestmp cache.
        $this->valuestmp = null;
    }

    public function save_response() {
        // TODO move to this class
        global $USER, $SESSION, $DB;

        $feedbackcompleted = $this->find_last_completed();
        $feedbackcompletedtmp = $this->get_current_completed_tmp();

        if (feedback_check_is_switchrole()) {
            // We do not actually save anything if the role is switched, just delete temporary values.
            feedback_delete_completedtmp($feedbackcompletedtmp->id); // TODO move to this class
            return;
        }

        // Save values.
        $completedid = feedback_save_tmp_values($feedbackcompletedtmp, $feedbackcompleted);
        $this->completed = $DB->get_record('feedback_completed', array('id' => $completedid));

        // Send email.
        if ($this->feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
            feedback_send_email($this->cm, $this->feedback, $this->cm->get_course(), $USER);
        } else {
            feedback_send_email_anonym($this->cm, $this->feedback, $this->cm->get_course());
        }

        unset($SESSION->feedback->is_started);

        // Update completion state
        $completion = new completion_info($this->cm->get_course());
        if (isloggedin() && !isguestuser() && $completion->is_enabled($this->cm) && $this->feedback->completionsubmit) {
            $completion->update_state($this->cm, COMPLETION_COMPLETE);
        }
    }

    /**
     * Retrieves the last completion record for the current user
     *
     * @return stdClass record from feedback_completed or false if not found
     */
    protected function find_last_completed() {
        global $USER, $DB;
        if (isloggedin() || isguestuser()) {
            // Not possible to retrieve completed feedback for guests.
            return false;
        }
        if ($this->is_anonymous()) {
            // Not possible to retrieve completed anonymous feedback.
            return false;
        }
        $params = array('feedback' => $this->feedback->id, 'userid' => $USER->id);
        if ($this->get_courseid()) {
            $params['courseid'] = $this->get_courseid();
        }
        $this->completed = $DB->get_record('feedback_completed', $params);
        return $this->completed;
    }

    public function can_complete() {
        global $CFG;

        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/feedback:complete', $context)) {
            return true;
        }

        if (!empty($CFG->feedback_allowfullanonymous)
                    AND $this->feedback->course == SITEID
                    AND $this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES
                    AND (!isloggedin() OR isguestuser())) {
            // Guests are allowed to complete fully anonymous feedback without having 'mod/feedback:complete' capability.
            return true;
        }

        return false;
    }
}