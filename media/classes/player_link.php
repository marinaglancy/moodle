<?php

/**
 * Special media player class that just puts a link.
 *
 * Always enabled, used as the last fallback.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_media_player_link extends core_media_player {
    public function embed($urls, $name, $width, $height, $options) {
        // If link is turned off, return empty.
        if (!empty($options[core_media_helper::OPTION_NO_LINK])) {
            return '';
        }

        // Build up link content.
        $output = '';
        foreach ($urls as $url) {
            $title = core_media_helper::get_filename($url);
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

    public function is_enabled() {
        // Cannot be disabled.
        return true;
    }

    public function get_rank() {
        return 0;
    }
}
