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
 * Contains class mod_feedback_structure
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Stores and manipulates the structure of the feedback or template (items, pages, etc.)
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_structure {
    /** @var stdClass */
    protected $feedback;
    /** @var cm_info */
    protected $cm;
    /** @var int */
    protected $courseid = 0;
    /** @var int */
    protected $templateid;
    /** @var array */
    protected $allitems;

    /**
     * Constructor
     *
     * @param stdClass $feedback feedback object, in case of the template
     *     this is the current feedback the template is accessed from
     * @param cm_info $cm course module object corresponding to the $feedback
     * @param int $courseid current course (for site feedbacks only)
     * @param int $templateid template id if this class represents the template structure
     */
    public function __construct($feedback, $cm, $courseid = 0, $templateid = null) {
        $this->feedback = $feedback;
        $this->cm = $cm;
        if ($feedback->course == SITEID) {
            $this->courseid = $courseid ?: SITEID;
        }
        $this->templateid = $templateid;
    }

    /**
     * Current feedback
     * @return stdClass
     */
    public function get_feedback() {
        return $this->feedback;
    }

    /**
     * Current course module
     * @return stdClass
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Id of the current course (for site feedbacks only)
     * @return stdClass
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Template id
     * @return int
     */
    public function get_templateid() {
        return $this->templateid;
    }

    /**
     * Is this feedback open (check timeopen and timeclose)
     * @return bool
     */
    public function is_open() {
        $checktime = time();
        return (!$this->feedback->timeopen || $this->feedback->timeopen <= $checktime) &&
            (!$this->feedback->timeclose || $this->feedback->timeclose >= $checktime);
    }

    /**
     * Get all items in this feedback or this template
     * @param bool $hasvalueonly only count items with a value.
     * @return array of objects from feedback_item with an additional attribute 'itemnr'
     */
    public function get_items($hasvalueonly = false) {
        global $DB;
        if ($this->allitems === null) {
            if ($this->templateid) {
                $this->allitems = $DB->get_records('feedback_item', ['template' => $this->templateid], 'position');
            } else {
                $this->allitems = $DB->get_records('feedback_item', ['feedback' => $this->feedback->id], 'position');
            }
            $idx = 1;
            foreach ($this->allitems as $id => $item) {
                $this->allitems[$id]->itemnr = $item->hasvalue ? ($idx++) : null;
            }
        }
        if ($hasvalueonly && $this->allitems) {
            return array_filter($this->allitems, function($item) {
                return $item->hasvalue;
            });
        }
        return $this->allitems;
    }

    /**
     * Is the items list empty?
     * @return bool
     */
    public function is_empty() {
        $items = $this->get_items();
        $displayeditems = array_filter($items, function($item) {
            return $item->typ !== 'pagebreak';
        });
        return !$displayeditems;
    }

    /**
     * Is this feedback anonymous?
     * @return bool
     */
    public function is_anonymous() {
        return $this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES;
    }

    /**
     * Returns the formatted text of the page after submit or null if it is not set
     *
     * @return string|null
     */
    public function page_after_submit() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $pageaftersubmit = $this->get_feedback()->page_after_submit;
        if (empty($pageaftersubmit)) {
            return null;
        }
        $pageaftersubmitformat = $this->get_feedback()->page_after_submitformat;

        $context = context_module::instance($this->get_cm()->id);
        $output = file_rewrite_pluginfile_urls($pageaftersubmit,
                'pluginfile.php', $context->id, 'mod_feedback', 'page_after_submit', 0);

        return format_text($output, $pageaftersubmitformat, array('overflowdiv' => true));
    }

    /**
     * Checks if current user is able to view feedback on this course.
     *
     * @return bool
     */
    function can_view_analysis() {
        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/feedback:viewreports', $context)) {
            return true;
        }

        if (intval($this->feedback->publish_stats) != 1 ||
                !has_capability('mod/feedback:viewanalysepage', $context)) {
            return false;
        }

        if (!isloggedin() || isguestuser()) {
            // There is no tracking for the guests, assume that they can view analysis if condition above is satisfied.
            return $this->feedback->course == SITEID;
        }

        return $this->is_already_submitted(true);
    }

    /**
     * check for multiple_submit = false.
     * if the feedback is global so the courseid must be given
     *
     * @return bool true if the feedback already is submitted otherwise false
     */
    public function is_already_submitted($anycourseid = false) {
        global $USER, $DB;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $params = array('userid' => $USER->id, 'feedback' => $this->feedback->id);
        if (!$anycourseid && $this->courseid) {
            $params['courseid'] = $this->courseid;
        }
        return $DB->record_exists('feedback_completed', $params);
    }

    /**
     * Check whether the feedback is mapped to the given courseid.
     */
    public function check_course_is_mapped() {
        global $DB;
        if ($this->feedback->course != SITEID) {
            return true;
        }
        if ($DB->get_records('feedback_sitecourse_map', array('feedbackid' => $this->feedback->id))) {
            $params = array('feedbackid' => $this->feedback->id, 'courseid' => $this->courseid);
            if (!$DB->get_record('feedback_sitecourse_map', $params)) {
                return false;
            }
        }
        // No mapping means any course is mapped.
        return true;
    }
}