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
 * Special media player class that just puts a link
 *
 * @package   core_media
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Special media player class that just puts a link.
 *
 * Always enabled, used as the last fallback.
 *
 * @package   core_media
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_media_player_link extends core_media_player {
    public function embed($urls, $name, $width, $height, $options) {
        // If link is turned off, return empty.
        if (!empty($options[core_media_manager::OPTION_NO_LINK])) {
            return '';
        }

        // Build up link content.
        $output = '';
        foreach ($urls as $url) {
            $title = core_media_manager::instance()->get_filename($url);
            $printlink = html_writer::link($url, $title, array('class' => 'mediafallbacklink'));
            if ($output) {
                // Where there are multiple available formats, there are fallback links
                // for all formats, separated by /.
                $output .= ' / ';
            }
            $output .= $printlink;
        }
        return $output;
    }

    public function list_supported_urls(array $urls, array $options = array()) {
        // Supports all URLs.
        return $urls;
    }

    /**
     * Returns if the current player is enabled.
     *
     * @deprecated since Moodle 3.2
     * @return bool True if player is enabled
     */
    public function is_enabled() {
        debugging('Function core_media_player::is_enabled() is deprecated without replacement', DEBUG_DEVELOPER);
        return true;
    }

    /**
     * Gets the ranking of this player comparing to other players.
     *
     * @return int Rank (higher is better)
     */
    public function get_rank() {
        return 0;
    }
}
