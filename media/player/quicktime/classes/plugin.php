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
 * Main class for plugin 'media_quicktime'
 *
 * @package   media_quicktime
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Media player using object tag and QuickTime player.
 *
 * @package   media_quicktime
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_quicktime_plugin extends core_media_player {
    public function embed($urls, $name, $width, $height, $options) {
        // Show first URL.
        $firsturl = reset($urls);
        $url = $firsturl->out(true);

        // Work out size.
        self::pick_video_size($width, $height);
        $size = 'width="' . $width . '" height="' . ($height + 15) . '"';

        // MIME type for object tag.
        $mimetype = core_media_manager::instance()->get_mimetype($firsturl);

        $fallback = core_media_player::PLACEHOLDER;

        // Embed code.
        return <<<OET
<span class="mediaplugin mediaplugin_qt">
    <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
            codebase="http://www.apple.com/qtactivex/qtplugin.cab" $size>
        <param name="pluginspage" value="http://www.apple.com/quicktime/download/" />
        <param name="src" value="$url" />
        <param name="controller" value="true" />
        <param name="loop" value="false" />
        <param name="autoplay" value="false" />
        <param name="autostart" value="false" />
        <param name="scale" value="aspect" />
        <!--[if !IE]><!-->
        <object data="$url" type="$mimetype" $size>
            <param name="src" value="$url" />
            <param name="pluginurl" value="http://www.apple.com/quicktime/download/" />
            <param name="controller" value="true" />
            <param name="loop" value="false" />
            <param name="autoplay" value="false" />
            <param name="autostart" value="false" />
            <param name="scale" value="aspect" />
        <!--<![endif]-->
            $fallback
        <!--[if !IE]><!-->
        </object>
        <!--<![endif]-->
    </object>
</span>
OET;
    }

    public function get_supported_extensions() {
        return array('.mpg', '.mpeg', '.mov', '.mp4', '.m4v', '.m4a');
    }

    /**
     * Default rank
     * @return int
     */
    public function get_rank() {
        return 10;
    }
}

