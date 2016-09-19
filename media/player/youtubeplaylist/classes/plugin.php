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
 * Main class for component 'media_youtubeplaylist'
 *
 * @package   media_youtubeplaylist
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Player that creates YouTube playlist embedding.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_youtubeplaylist_plugin extends core_media_player_external {

    protected function embed_external(moodle_url $url, $name, $width, $height, $options) {
        $site = $this->matches[1];
        $playlist = $this->matches[3];

        $info = trim($name);
        if (empty($info) or strpos($info, 'http') === 0) {
            $info = get_string('siteyoutube', 'core_media');
        }
        $info = s($info);

        self::pick_video_size($width, $height);

        return <<<OET
<span class="mediaplugin mediaplugin_youtube">
<iframe width="$width" height="$height" src="https://$site/embed/videoseries?list=$playlist" frameborder="0" allowfullscreen="1"></iframe>
</span>
OET;
    }

    protected function get_regex() {
        // Initial part of link.
        $start = '~^https?://(www\.youtube(-nocookie)?\.com)/';
        // Middle bit: either view_play_list?p= or p/ (doesn't work on youtube) or playlist?list=.
        $middle = '(?:view_play_list\?p=|p/|playlist\?list=)([a-z0-9\-_]+)';
        return $start . $middle . core_media_player_external::END_LINK_REGEX_PART;
    }

    public function get_embeddable_markers() {
        return array('youtube');
    }
}

