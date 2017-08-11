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
 * Contains class mod_feedback\output\analysis
 *
 * @package   mod_feedback
 * @copyright 2017 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_feedback\output;

use templatable;
use renderer_base;
use stdClass;

/**
 * Class to help display feedback analysis
 *
 * @package   mod_feedback
 * @copyright 2017 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analysis implements templatable {

    /** @var \mod_feedback_structure */
    protected $feedbackstructure;

    /** @var int */
    protected $mygroupid;

    /**
     * Constructor.
     *
     * @param \mod_feedback_structure $feedbackstructure
     * @param int $mygroupid currently selected group
     */
    public function __construct($feedbackstructure, $mygroupid = false) {
        $this->feedbackstructure = $feedbackstructure;
        $this->mygroupid = $mygroupid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        // Check if we have enough results to print analysis.
        if (!$this->feedbackstructure->has_sufficient_responses_for_group($this->mygroupid)) {
            $notification = new \core\output\notification(get_string('insufficient_responses_for_this_group', 'feedback'));
            $data->insufficientdata = $notification->export_for_template($output);
            return $data;
        }

        $data->items = [];
        $items = $this->feedbackstructure->get_items(true);
        $courseid = $this->feedbackstructure->get_courseid();
        $autonumbering = $this->feedbackstructure->get_feedback()->autonumbering;
        foreach ($items as $item) {
            $itemobj = feedback_get_item_class($item->typ);
            $analyseditem = $itemobj->get_analysis($item, $this->mygroupid, $courseid);
            if ($analyseditem->hasdata) {
                $analyseditem->itemnr = $autonumbering ? $item->itemnr : '';
                $data->items[] = $analyseditem;
            }
        }

        return $data;
    }
}
