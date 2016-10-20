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
 * Main class for plugin 'media_flowplayerflash'
 *
 * @package   media_flowplayerflash
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Flash video player inserted using JavaScript.
 *
 * @package   media_flowplayerflash
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_flowplayerflash_plugin extends core_media_player {
    /** @var moodle_page caches last moodle page used to include AMD module */
    protected $loadedonpage = null;

    public function embed($urls, $name, $width, $height, $options) {
        global $PAGE;

        // Use first url (there can actually be only one unless some idiot
        // enters two mp3 files as alternatives).
        $url = reset($urls);

        if (core_media_manager::instance()->get_extension($url) === 'mp3') {

            // Unique id even across different http requests made at the same time
            // (for AJAX, iframes).
            $id = 'core_media_mp3_' . md5(time() . '_' . rand());

            // When Flash or JavaScript are not available only the fallback is displayed,
            // using span not div because players are inline elements.
            $spanparams = array('id' => $id, 'class' => 'mediaplugin mediaplugin_mp3');
            if ($width) {
                $spanparams['style'] = 'width: ' . $width . 'px';
            }
            $output = html_writer::tag('span', core_media_player::PLACEHOLDER, $spanparams);
            // We can not use standard JS init because this may be cached
            // note: use 'small' size unless embedding in block mode.
            $PAGE->requires->js_call_amd('media_flowplayerflash/loader', 'add_audio_player',
                array($id, $url->out(false),
                    empty($options[core_media_manager::OPTION_BLOCK])));

            return $output;

        } else {

            // Unique id even across different http requests made at the same time
            // (for AJAX, iframes).
            $id = 'core_media_flv_' . md5(time() . '_' . rand());

            // Compute width and height.
            $autosize = false;
            if (!$width && !$height) {
                self::pick_video_size($width, $height);
                $autosize = true;
            }

            // Fallback span (will normally contain link).
            $output = html_writer::tag('span', core_media_player::PLACEHOLDER,
                array('id' => $id, 'class' => 'mediaplugin mediaplugin_flv'));
            // We can not use standard JS init because this may be cached.
            $PAGE->requires->js_call_amd('media_flowplayerflash/loader', 'add_video_player',
                array($id, addslashes_js($url->out(false)),
                $width, $height, $autosize));
            return $output;

        }
    }

    public function get_supported_extensions() {
        $config = get_config('media_flowplayerflash');
        $rv = [];
        if ($config->mp3) {
            $rv[] = '.mp3';
        }
        if ($config->flv) {
            $rv[] = '.flv';
            $rv[] = '.f4v';
        }
        return $rv;
    }

    /**
     * Default rank
     * @return int
     */
    public function get_rank() {
        return 80;
    }
}

