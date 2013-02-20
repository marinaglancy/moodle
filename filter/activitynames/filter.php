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
 * This filter provides automatic linking to
 * activities when its name (title) is found inside every Moodle text
 *
 * @package    filter_activitynames
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Activity name filtering
 *
 * This filter provides automatic linking to
 * activities when its name (title) is found inside every Moodle text.
 * If called in module context it does not link to the same module's name
 *
 * @package    filter_activitynames
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_activitynames extends moodle_text_filter {

    /**
     * Filters the text adding links to activities in the same course
     *
     * @param $text some HTML content.
     * @param array $options options passed to the filters
     * @return the HTML content after the filtering has been applied.
     */
    function filter($text, array $options = array()) {
        if (!$courseid = get_courseid_from_context($this->context)) {
            return $text;
        }

        // Check for cached list of activities inside the course.
        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'filter_activitynames',
                'activitylist', array('simplekeys' => true));
        $activitylist = $cache->get($courseid);

        if ($activitylist === false) {
            $activitylist = array();

            $modinfo = get_fast_modinfo($courseid);
            if (!empty($modinfo->cms)) {
                // Create list of course modules sorted by name length.
                // Exclude course modules without links, hidden activities and activities for group members only.
                $sortedactivities = array();
                foreach ($modinfo->cms as $cm) {
                    if ($cm->visible && empty($cm->groupmembersonly) &&
                            strlen(trim(strip_tags($cm->name))) && $cm->has_view()) {
                        $sortedactivities[] = $cm;
                    }
                }
                usort($sortedactivities, array($this, 'comparemodulenamesbylength'));

                // For each course module create and store filterobject(s)
                foreach ($sortedactivities as $cm) {
                    $title = s(trim(strip_tags($cm->name)));
                    $currentname = trim($cm->name);
                    $entitisedname = s($currentname);
                    $href_tag_begin = html_writer::start_tag('a',
                            array('class' => 'autolink', 'title' => $title,
                                'href' => $cm->get_url()));
                    $activitylist[$cm->id] = new filterobject($currentname, $href_tag_begin, '</a>', false, true);
                    if ($currentname != $entitisedname) {
                        // If name has some entity (&amp; &quot; &lt; &gt;) add that filter too. MDL-17545.
                        $activitylist[$cm->id.'-e'] = new filterobject($entitisedname, $href_tag_begin, '</a>', false, true);
                    }
                }
            }

            // store activitylist in cahce but only for one course
            $cache->purge();
            $cache->set($courseid, $activitylist);
        }

        if ($activitylist && $this->context->contextlevel == CONTEXT_MODULE) {
            // remove filterobjects for the current module
            $cmid = $this->context->instanceid;
            if (isset($activitylist[$cmid])) {
                $activitylist = array_diff_key($activitylist, array($cmid => 1, $cmid.'-e' => 1));
            }
        }

        if ($activitylist) {
            return filter_phrases($text, $activitylist);
        } else {
            return $text;
        }
    }

    /**
     * Used to order module names from longer to shorter
     */
    function comparemodulenamesbylength($a, $b)  {
        if (strlen($a->name) == strlen($b->name)) {
            return 0;
        }
        return (strlen($a->name) < strlen($b->name)) ? 1 : -1;
    }
}
