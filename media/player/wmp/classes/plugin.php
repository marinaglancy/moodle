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
 * Main class for plugin 'media_wmp'
 *
 * @package   media_wmp
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Embeds Windows Media Player using object tag.
 *
 * @package   media_wmp
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_wmp_plugin extends core_media_player {
    public function embed($urls, $name, $width, $height, $options) {
        // Get URL (we just use first, probably there is only one).
        $firsturl = reset($urls);
        $url = $firsturl->out(false);

        // Work out width.
        if (!$width || !$height) {
            self::pick_video_size($width, $height);
            // Object tag has default size.
            $mpsize = '';
            $size = 'width="' . $width .
                '" height="' . ($height + 64) . '"';
            $autosize = 'true';
        } else {
            $size = 'width="' . $width . '" height="' . ($height + 15) . '"';
            $mpsize = 'width="' . $width . '" height="' . ($height + 64) . '"';
            $autosize = 'false';
        }

        // MIME type for object tag.
        $mimetype = core_media_manager::instance()->get_mimetype($firsturl);

        $fallback = core_media_player::PLACEHOLDER;

        // Embed code.
        return <<<OET
<span class="mediaplugin mediaplugin_wmp">
    <object classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" $mpsize
            standby="Loading Microsoft(R) Windows(R) Media Player components..."
            type="application/x-oleobject">
        <param name="Filename" value="$url" />
        <param name="src" value="$url" />
        <param name="url" value="$url" />
        <param name="ShowControls" value="true" />
        <param name="AutoRewind" value="true" />
        <param name="AutoStart" value="false" />
        <param name="Autosize" value="$autosize" />
        <param name="EnableContextMenu" value="true" />
        <param name="TransparentAtStart" value="false" />
        <param name="AnimationAtStart" value="false" />
        <param name="ShowGotoBar" value="false" />
        <param name="EnableFullScreenControls" value="true" />
        <param name="uimode" value="full" />
        <!--[if !IE]><!-->
        <object data="$url" type="$mimetype" $size>
            <param name="src" value="$url" />
            <param name="controller" value="true" />
            <param name="autoplay" value="false" />
            <param name="autostart" value="false" />
            <param name="resize" value="scale" />
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
        return array('.wmv', '.avi');
    }

    /**
     * Default rank
     * @return int
     */
    public function get_rank() {
        return 60;
    }
}
