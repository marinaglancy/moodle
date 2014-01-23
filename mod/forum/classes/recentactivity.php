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
 * Class to represent an item in mod_forum recent activity.
 *
 * @package    mod_forum
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum;
defined('MOODLE_INTERNAL') || die();

/**
 * Class to represent an item in mod_forum recent activity.
 *
 * @package    mod_forum
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
        $a->type = 'forum';
        $a->course = $cm->get_course();
        $a->cm = $cm;
        $a->timestamp = $timemodified;
        $a->content = $content;
        $a->user = $user;
        return $a;
    }

    public static function get_recentactivity_types($forblock = false) {
        return array(
            'forum' => new \lang_string('newforumposts', 'forum')
        );
    }

    public static function get_recentactivity($course, $timestart, $filters = array(), $forblock = false) {
        global $CFG, $USER, $DB;

        $groupid = isset($filters['groupid']) ? $filters['groupid'] : "";
        $userid = isset($filters['userid']) ? $filters['userid'] : "";

        $params = array($timestart);

        if (isset($filters['cm'])) {
            $forumselect = "AND f.id = ?";
            $params[] = $filters['cm']->instance;
            $modinfo = $filters['cm']->get_modinfo();
        } else {
            $forumselect = "AND f.course = ?";
            $params[] = $course->id;
            $modinfo = get_fast_modinfo($course);
        }

        if ($userid) {
            $userselect = "AND u.id = ?";
            $params[] = $userid;
        } else {
            $userselect = "";
        }

        if ($groupid) {
            $groupselect = "AND d.groupid = ?";
            $params[] = $groupid;
        } else {
            $groupselect = "";
        }

        $allnames = \user_picture::fields('u', null, 'userid');
        if (!$posts = $DB->get_records_sql("SELECT p.*, f.type AS forumtype, d.forum, d.groupid,
                                                  d.timestart, d.timeend, $allnames
                                             FROM {forum_posts} p
                                                  JOIN {forum_discussions} d ON d.id = p.discussion
                                                  JOIN {forum} f             ON f.id = d.forum
                                                  JOIN {user} u              ON u.id = p.userid
                                            WHERE p.created > ?
                                                  $forumselect
                                                  $userselect $groupselect
                                         ORDER BY p.id ASC", $params)) { // order by initial posting date
             return array();
        }

        $activities = array();
        foreach ($posts as $post) {
            if (!isset($modinfo->instances['forum'][$post->forum])) {
                // not visible
                continue;
            }
            $cm = $modinfo->instances['forum'][$post->forum];
            if (!$cm->uservisible) {
                continue;
            }

            $groupmode       = $cm->effectivegroupmode;
            $viewhiddentimed = has_capability('mod/forum:viewhiddentimedposts', $cm->context);
            $accessallgroups = has_capability('moodle/site:accessallgroups', $cm->context);

            if (!empty($CFG->forum_enabletimedposts) and $USER->id != $post->duserid
              and (($post->timestart > 0 and $post->timestart > time()) or ($post->timeend > 0 and $post->timeend < time()))) {
                if (!$viewhiddentimed) {
                    continue;
                }
            }

            if ($groupmode) {
                if ($post->groupid == -1 or $groupmode == VISIBLEGROUPS or $accessallgroups) {
                    // oki (Open discussions have groupid -1)
                } else {
                    // separate mode
                    if (isguestuser()) {
                        // shortcut
                        continue;
                    }

                    if (!in_array($post->groupid, $modinfo->get_groups($cm->groupingid))) {
                        continue;
                    }
                }
            }

            $content = new \stdClass();
            $content->id         = $post->id;
            $content->discussion = $post->discussion;
            $content->subject    = format_string($post->subject, true, array('context' => $cm->context));
            $content->parent     = $post->parent;

            $activities[] = static::create($cm, $post->modified, $content,
                    \user_picture::unalias($post, null, 'userid'));
        }

        return $activities;
    }

    public function display($detail = true) {
        global $CFG, $OUTPUT;

        if ($this->content->parent) {
            $class = 'reply';
        } else {
            $class = 'discussion';
        }

        echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

        echo "<tr><td class=\"userpicture\" valign=\"top\">";
        echo $OUTPUT->user_picture($this->user, array('courseid'=>$this->cm->course));
        echo "</td><td class=\"$class\">";

        echo '<div class="title">';
        if ($detail) {
            $aname = s($this->cm->get_formatted_name());
            echo "<img src=\"" . $OUTPUT->pix_url('icon', $this->cm->modname) . "\" ".
                 "class=\"icon\" alt=\"{$aname}\" />";
        }
        echo "<a href=\"$CFG->wwwroot/mod/forum/discuss.php?d={$this->content->discussion}"
             ."#p{$this->content->id}\">{$this->content->subject}</a>";
        echo '</div>';

        echo '<div class="user">';
        $fullname = fullname($this->user, has_capability('moodle/site:viewfullnames', $this->cm->context));
        echo "<a href=\"$CFG->wwwroot/user/view.php?id={$this->user->id}&amp;course={$this->cm->course}\">"
             ."{$fullname}</a> - ".userdate($this->timestamp);
        echo '</div>';
        echo "</td></tr></table>";
    }

    public function display_in_block() {
        global $CFG;
        $subjectclass = empty($this->content->parent) ? ' bold' : '';
        $output = $this->display_in_block_header().
                '<div class="info'.$subjectclass.'">';
        if (empty($this->content->parent)) {
            $output .= '"<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$this->content->discussion.'">';
        } else {
            $output .= '"<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$this->content->discussion.'&amp;parent='.
                    $this->content->parent.'#p'.$this->content->id.'">';
        }
        $output .= break_up_long_words($this->content->subject);
        $output .= "</a>\"</div>";
        return $output;
    }
}
