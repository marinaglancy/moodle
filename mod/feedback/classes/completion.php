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
class mod_feedback_completion {
    /** @var mod_feedback_structure */
    protected $structure;
    /** @var int */
    protected $completed;

    /** @var stdClass */
    protected $completedtmp = null;
    /** @var stdClass[] */
    protected $valuestmp = null;
    /** @var stdClass[] */
    protected $values = null;


    /**
     * Constructor
     *
     * @param stdClass $structure
     * @param stdClass $completed can be specified when viewing the feedback completed by somebody else
     */
    public function __construct($structure, $completed = null) {
        $this->structure = $structure;
        $this->completed = $completed;
    }

    /**
     * Retrieves a record from 'feedback_completed' table for a given response
     *
     * @param stdClass $feedback
     * @param int $completedid id in the table feedback_completed, may be omitted if userid is specified
     *     but it is highly recommended because the same user may have multiple responses to the same feedback
     *     for different courses
     * @param int $userid id of the user - if specified only non-anonymous replies will be returned. If not
     *     specified only anonymous replies will be returned and the $completedid is mandatory.
     * @param int $strictness
     * @return stdClass
     */
    public static function get_completed($feedback, $completedid, $userid = null, $strictness = IGNORE_MISSING) {
        global $DB;
        $anonymous = $userid ? FEEDBACK_ANONYMOUS_NO : FEEDBACK_ANONYMOUS_YES;
        $params = array('feedback' => $feedback->id, 'anonymous_response' => $anonymous);
        if (!$userid && !$completedid) {
            throw new coding_exception('Either $completedid or $userid must be specified');
        }
        if ($completedid) {
            $params['id'] = $completedid;
        }
        if ($userid) {
            $params['userid'] = $userid;
        }
        return $DB->get_record('feedback_completed', $params, '*', $strictness);
    }

    /**
     * Returns feedback structre
     * @return mod_feedback_structure
     */
    public function get_structure() {
        return $this->structure;
    }

    /**
     * Current feedback
     * @return stdClass
     */
    public function get_feedback() {
        return $this->structure->get_feedback();
    }

    /**
     * Current course module
     * @return stdClass
     */
    public function get_cm() {
        return $this->structure->get_cm();
    }

    /**
     * Id of the current course (for site feedbacks only)
     * @return stdClass
     */
    public function get_courseid() {
        $this->structure->get_courseid();
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
        $allitems = $this->structure->get_items();
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
        $items = $this->structure->get_items();
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
     * to the first page after the last page he completed. This can be
     * @return int
     */
    public function get_resume_page() {
        list($lastcompleted, $firstincompleted) = $this->get_last_completed_page();
        $resumepage = $this->get_next_page($lastcompleted === null ? -1 : $lastcompleted, false);
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
}