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
 * Main class for plugin 'media_ableplayer'
 *
 * @package   media_ableplayer
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Player that creates HTML5 <video> tag.
 *
 * @package   media_ableplayer
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_ableplayer_plugin extends core_media_player_native {
    /** @var moodle_page caches last moodle page used to include AMD module */
    protected $loadedonpage = null;
    /** @var array caches supported extensions */
    protected $extensions = null;

    /**
     * Generates code required to embed the player.
     *
     * @param array $urls
     * @param string $name
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     */
    public function embed($urls, $name, $width, $height, $options) {
        $sources = array();
        $mediamanager = core_media_manager::instance();

        $text = null;
        $isaudio = null;
        if (array_key_exists(core_media_manager::OPTION_ORIGINAL_TEXT, $options) &&
            preg_match('/^<(video|audio)\b/i', $options[core_media_manager::OPTION_ORIGINAL_TEXT], $matches)) {
            // Original text already had media tag - get some data from it.
            $text = $options[core_media_manager::OPTION_ORIGINAL_TEXT];
            $isaudio = strtolower($matches[1]) === 'audio';
        }

        // Build list of source tags.
        foreach ($urls as $url) {
            $mimetype = $mediamanager->get_mimetype($url);
            $source = html_writer::empty_tag('source', array('src' => $url, 'type' => $mimetype));
            $sources[] = $source;
            if ($isaudio === null) {
                $isaudio = in_array('.' . $mediamanager->get_extension($url), file_get_typegroup('extension', 'audio'));
            }
        }
        $sources = implode("\n", $sources);

        // Find the title, prevent double escaping.
        $title = $this->get_name($name, $urls);
        $title = preg_replace(['/&amp;/', '/&gt;/', '/&lt;/'], ['&', '>', '<'], $title);

        static $ablecounter = 1;
        $idtag = 'id="id_ableplayer_' . ($ablecounter++) . '"';

        $this->load_amd_module();

        $path = new moodle_url('/media/player/ableplayer');

        if ($text !== null) {
            // Original text already had media tag - add necessary attributes and replace sources
            // with the supported URLs only.
            $text = self::remove_attributes($text, ['id', 'controls', 'width', 'height']);
            $attributes = ['id' => $idtag, 'data-able-player' => 'true', 'data-root-path' => $path->out(false)];
            if (self::get_attribute($text, 'title') === null) {
                $attributes['title'] = $title;
            }
            $text = self::add_attributes($text, $attributes);
            $text = self::replace_sources($text, $sources);
        } else {
            // Create <video> or <audio> tag with necessary attributes and all sources.
            $attrs = ['id' => $idtag, 'data-able-player' => 'true', 'data-root-path' => $path->out(false),
                    'preload' => 'auto', 'title' => $title];
            $text = html_writer::tag($isaudio ? 'audio' : 'video', $sources . self::LINKPLACEHOLDER, $attrs);
        }

        // Limit the size of the video if width is specified.
        // Note that we do not set any limits on the height because Able player displayes the controls
        // and subtitles/cc area below the video. Height will be calculated from the video aspect rate.
        self::pick_video_size($width, $height);
        if ($width) {
            $text = html_writer::div($text, null, ['style' => 'max-width:' . $width . 'px;margin:auto;']);
        }

        return '<div class="mediaplugin mediaplugin_ableplayer">'.$text.'</div>';
    }

    /**
     * Utility function that sets width and height to defaults if not specified
     * as a parameter to the function (will be specified either if, (a) the calling
     * code passed it, or (b) the URL included it).
     * @param int $width Width passed to function (updated with final value)
     * @param int $height Height passed to function (updated with final value)
     */
    protected static function pick_video_size(&$width, &$height) {
        if (!get_config('media_ableplayer', 'limitsize')) {
            return;
        }
        parent::pick_video_size($width, $height);
    }

    /**
     * Makes sure the player is loaded on the page and the language strings are set.
     * We only need to do it once on a page.
     */
    protected function load_amd_module() {
        global $PAGE;
        if ($this->loadedonpage && $PAGE === $this->loadedonpage) {
            // This is exactly the same page object we used last time.
            // Prevent from calling multiple times on the same page.
            return;
        }

        $PAGE->requires->js_amd_inline('require(["media_ableplayer/ablewrapper"], function() {});');

        $this->loadedonpage = $PAGE;
    }

    public function get_supported_extensions() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        if ($this->extensions === null) {
            $filetypes = preg_split('/\s*,\s*/',
                strtolower(trim(get_config('media_ableplayer', 'audioextensions') . ', ' .
                    get_config('media_ableplayer', 'videoextensions'))));
            $this->extensions = file_get_typegroup('extension', $filetypes);
            if ($this->extensions) {
                // If Flash is disabled only return extensions natively supported by browsers.
                $nativeextensions = array_merge(file_get_typegroup('extension', 'html_video'),
                    file_get_typegroup('extension', 'html_audio'));
                $this->extensions = array_intersect($this->extensions, $nativeextensions);
            }
        }
        return $this->extensions;
    }

    /**
     * Default rank
     * @return int
     */
    public function get_rank() {
        return 90;
    }
}
