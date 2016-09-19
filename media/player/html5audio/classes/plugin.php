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
 * Main class for component 'media_html5audio'
 *
 * @package   media_html5audio
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();



/**
 * Player that creates HTML5 <audio> tag.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_html5audio_plugin extends core_media_player {
    public function embed($urls, $name, $width, $height, $options) {

        // Build array of source tags.
        $sources = array();
        foreach ($urls as $url) {
            $mimetype = core_media_manager::instance()->get_mimetype($url);
            $sources[] = html_writer::empty_tag('source', array('src' => $url, 'type' => $mimetype));
        }

        $sources = implode("\n", $sources);
        $title = $this->get_name($name, $urls);
        // Escape title but prevent double escaping.
        $title = s(preg_replace(['/&amp;/', '/&gt;/', '/&lt;/'], ['&', '>', '<'], $title));

        // Default to not specify size (so it can be changed in css).
        $size = '';
        if ($width) {
            $size = 'width="' . $width . '"';
        }

        $fallback = core_media_player::PLACEHOLDER;

        return <<<OET
<audio controls="true" $size class="mediaplugin mediaplugin_html5audio" preload="none" title="$title">
$sources
$fallback
</audio>
OET;
    }

    public function get_supported_extensions() {
        return array('ogg', 'oga', 'aac', 'm4a', 'mp3');
    }

    public function list_supported_urls(array $urls, array $options = array()) {
        $extensions = $this->get_supported_extensions();
        $result = array();
        foreach ($urls as $url) {
            $ext = core_media_manager::instance()->get_extension($url);
            if (in_array($ext, $extensions)) {
                if ($ext === 'ogg' || $ext === 'oga') {
                    // Formats .ogg and .oga are not supported in IE, Edge, or Safari.
                    if (core_useragent::is_ie() || core_useragent::is_edge() || core_useragent::is_safari()) {
                        continue;
                    }
                } else {
                    // Formats .aac, .mp3, and .m4a are not supported in Opera.
                    if (core_useragent::is_opera()) {
                        continue;
                    }
                    // Formats .mp3 and .m4a were not reliably supported in Firefox before 27.
                    // https://developer.mozilla.org/en-US/docs/Web/HTML/Supported_media_formats
                    // has the details. .aac is still not supported.
                    if (core_useragent::is_firefox() && ($ext === 'aac' ||
                            !core_useragent::check_firefox_version(27))) {
                        continue;
                    }
                }
                // Old Android versions (pre 2.3.3) 'support' audio tag but no codecs.
                if (core_useragent::is_webkit_android() &&
                    !core_useragent::is_webkit_android('533.1')) {
                    continue;
                }

                $result[] = $url;
            }
        }
        return $result;
    }
}

