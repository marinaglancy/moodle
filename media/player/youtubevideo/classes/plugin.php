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
 * Main class for component 'media_youtubevideo'
 *
 * @package   media_youtubevideo
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Player that creates youtubevideo embedding.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_youtubevideo_plugin extends core_media_player_external {
    protected function embed_external(moodle_url $url, $name, $width, $height, $options) {
        $videoid = end($this->matches);

        $info = trim($name);
        if (empty($info) or strpos($info, 'http') === 0) {
            $info = get_string('siteyoutube', 'core_media');
        }
        $info = s($info);

        self::pick_video_size($width, $height);

        $params = '';
        $start = self::get_start_time($url);
        if ($start > 0) {
            $params .= "start=$start&amp;";
        }

        $listid = $url->param('list');
        // Check for non-empty but valid playlist ID.
        if (!empty($listid) && !preg_match('/[^a-zA-Z0-9\-_]/', $listid)) {
            // This video is part of a playlist, and we want to embed it as such.
            $params .= "list=$listid&amp;";
        }

        return <<<OET
<span class="mediaplugin mediaplugin_youtube">
<iframe title="$info" width="$width" height="$height"
  src="https://www.youtube.com/embed/$videoid?{$params}rel=0&amp;wmode=transparent" frameborder="0" allowfullscreen="1"></iframe>
</span>
OET;

    }

    /**
     * Check for start time parameter.  Note that it's in hours/mins/secs in the URL,
     * but the embedded player takes only a number of seconds as the "start" parameter.
     * @param moodle_url $url URL of video to be embedded.
     * @return int Number of seconds video should start at.
     */
    protected static function get_start_time($url) {
        $matches = array();
        $seconds = 0;

        $rawtime = $url->param('t');
        if (empty($rawtime)) {
            $rawtime = $url->param('start');
        }

        if (is_numeric($rawtime)) {
            // Start time already specified as a number of seconds; ensure it's an integer.
            $seconds = $rawtime;
        } else if (preg_match('/(\d+?h)?(\d+?m)?(\d+?s)?/i', $rawtime, $matches)) {
            // Convert into a raw number of seconds, as that's all embedded players accept.
            for ($i = 1; $i < count($matches); $i++) {
                if (empty($matches[$i])) {
                    continue;
                }
                $part = str_split($matches[$i], strlen($matches[$i]) - 1);
                switch ($part[1]) {
                    case 'h':
                        $seconds += 3600 * $part[0];
                        break;
                    case 'm':
                        $seconds += 60 * $part[0];
                        break;
                    default:
                        $seconds += $part[0];
                }
            }
        }

        return intval($seconds);
    }

    protected function get_regex() {
        // Regex for standard youtube link
        $link = '(youtube(-nocookie)?\.com/(?:watch\?v=|v/))';
        // Regex for shortened youtube link
        $shortlink = '((youtu|y2u)\.be/)';

        // Initial part of link.
        $start = '~^https?://(www\.)?(' . $link . '|' . $shortlink . ')';
        // Middle bit: Video key value
        $middle = '([a-z0-9\-_]+)';
        return $start . $middle . core_media_player_external::END_LINK_REGEX_PART;
    }

    public function get_rank() {
        // I decided to make the link-embedding ones (that don't handle file
        // formats) have ranking in the 1000 range.
        return 1001;
    }

    public function get_embeddable_markers() {
        return array('youtube.com', 'youtube-nocookie.com', 'youtu.be', 'y2u.be');
    }
}
