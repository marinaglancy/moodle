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
 * Class to represent an item in mod_wiki recent activity.
 *
 * @package    mod_wiki
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wiki;
defined('MOODLE_INTERNAL') || die();

/**
 * Class to represent an item in mod_wiki recent activity.
 *
 * @package    mod_wiki
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recentactivity extends \core_course\recentactivity {
    /**
     * @var \cm_info $cm
     */
    var $cm;

    protected static function create($cm, $timemodified, $content, $user) {
        $a = new static();
        $a->type = 'wiki';
        $a->course = $cm->get_course();
        $a->cm = $cm;
        $a->timestamp = $timemodified;
        $a->content = $content;
        $a->user = $user;
        return $a;
    }

    public static function get_recentactivity_types($forblock = false) {
        if ($forblock) {
            return array(
                'wiki' => new \lang_string('updatedwikipages', 'wiki')
            );
        } else {
            return array();
        }
    }

    public static function get_recentactivity($course, $timestart, $filters = array(), $forblock = false) {
        global $CFG, $USER, $DB;
        $activities = array();
        if (!$forblock) {
            // Wiki activities are only displayed in a block.
            return $activities;
        }
        return $activities;
    }

    public function display($detail = true) {
        return '';
    }

    public function display_in_block() {
        return parent::display_in_block();
    }
}
