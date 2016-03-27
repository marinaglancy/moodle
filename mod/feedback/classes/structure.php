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
     * @return array of objects from feedback_item with an additional attribute 'itemnr'
     */
    public function get_items() {
        global $DB;
        if ($this->allitems === null) {
            if ($this->templateid) {
                $this->allitems = $DB->get_records('feedback_item', ['template' => $this->templateid], 'position');
            } else {
                $this->allitems = $DB->get_records('feedback_item', ['feedback' => $this->feedback->id], 'position');
            }
            $idx = 1;
            foreach ($this->allitems as $id => $item) {
                $item->itemnr = $item->hasvalue ? ($idx++) : null;
            }
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

    public function is_anonymous() {
        return $this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES;
    }

    /**
     * Checks if user is prevented from re-submission.
     * @return boolean
     */
    public function can_submit() {
        if ($this->get_feedback()->multiple_submit == 0 ) {
            if (feedback_is_already_submitted($this->get_feedback()->id, $this->get_courseid())) {
                return false;
            }
        }
        return true;
    }

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
}