<?php


/**
 * Player that embeds Vimeo links.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_vimeo_plugin extends core_media_player_external {
    protected function embed_external(moodle_url $url, $name, $width, $height, $options) {
        $videoid = $this->matches[1];
        $info = s($name);

        // Note: resizing via url is not supported, user can click the fullscreen
        // button instead. iframe embedding is not xhtml strict but it is the only
        // option that seems to work on most devices.
        self::pick_video_size($width, $height);

        $output = <<<OET
<span class="mediaplugin mediaplugin_vimeo">
<iframe title="$info" src="https://player.vimeo.com/video/$videoid"
  width="$width" height="$height" frameborder="0"
  webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
</span>
OET;

        return $output;
    }

    protected function get_regex() {
        // Initial part of link.
        $start = '~^https?://vimeo\.com/';
        // Middle bit: either watch?v= or v/.
        $middle = '([0-9]+)';
        return $start . $middle . core_media_player_external::END_LINK_REGEX_PART;
    }

    public function get_rank() {
        return 1010;
    }

    public function get_embeddable_markers() {
        return array('vimeo.com/');
    }
}
