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
    protected $completeid;

    /**
     * Constructor
     *
     * @param stdClass $structure
     * @param int $completeid can be specified when viewing the feedback completed by somebody else
     */
    public function __construct($structure, $completeid = null) {
        $this->structure = $structure;
        $this->completeid = $completeid;
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
}