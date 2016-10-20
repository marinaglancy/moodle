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
 * Main class for plugin 'media_realplayer'
 *
 * @package   media_realplayer
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Media player using object tag and RealPlayer.
 *
 * Hopefully nobody is using this obsolete format any more!
 *
 * @package   media_realplayer
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_realplayer_plugin extends core_media_player {
    public function embed($urls, $name, $width, $height, $options) {
        // Show first URL.
        $firsturl = reset($urls);
        $url = $firsturl->out(true);

        // Get name to use as title.
        $info = s($this->get_name($name, $urls));

        // The previous version of this code has the following comment, which
        // I don't understand, but trust it is correct:
        // Note: the size is hardcoded intentionally because this does not work anyway!
        $width = $height = 0;
        self::pick_video_size($width, $height);

        $fallback = core_media_player::PLACEHOLDER;
        return <<<OET
<span class="mediaplugin mediaplugin_real">
    <object title="$info" classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA"
            data="$url" width="$width" height="$height"">
        <param name="src" value="$url" />
        <param name="controls" value="All" />
        <!--[if !IE]><!-->
        <object title="$info" type="audio/x-pn-realaudio-plugin"
                data="$url" width="$width" height="$height">
            <param name="src" value="$url" />
            <param name="controls" value="All" />
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
        return array('.ra', '.ram', '.rm', '.rv');
    }

    /**
     * Default rank
     * @return int
     */
    public function get_rank() {
        return 40;
    }
}

