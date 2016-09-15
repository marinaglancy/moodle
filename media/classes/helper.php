<?php

/**
 * Constants and static utility functions for use with core_media_renderer.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class core_media_helper {
    /**
     * Option: Disable text link fallback.
     *
     * Use this option if you are going to print a visible link anyway so it is
     * pointless to have one as fallback.
     *
     * To enable, set value to true.
     */
    const OPTION_NO_LINK = 'nolink';

    /**
     * Option: When embedding, if there is no matching embed, do not use the
     * default link fallback player; instead return blank.
     *
     * This is different from OPTION_NO_LINK because this option still uses the
     * fallback link if there is some kind of embedding. Use this option if you
     * are going to check if the return value is blank and handle it specially.
     *
     * To enable, set value to true.
     */
    const OPTION_FALLBACK_TO_BLANK = 'embedorblank';

    /**
     * Option: Enable players which are only suitable for use when we trust the
     * user who embedded the content.
     *
     * At present, this option enables the SWF player.
     *
     * To enable, set value to true.
     */
    const OPTION_TRUSTED = 'trusted';

    /**
     * Option: Put a div around the output (if not blank) so that it displays
     * as a block using the 'resourcecontent' CSS class.
     *
     * To enable, set value to true.
     */
    const OPTION_BLOCK = 'block';

    /**
     * Given a string containing multiple URLs separated by #, this will split
     * it into an array of moodle_url objects suitable for using when calling
     * embed_alternatives.
     *
     * Note that the input string should NOT be html-escaped (i.e. if it comes
     * from html, call html_entity_decode first).
     *
     * @param string $combinedurl String of 1 or more alternatives separated by #
     * @param int $width Output variable: width (will be set to 0 if not specified)
     * @param int $height Output variable: height (0 if not specified)
     * @return array Array of 1 or more moodle_url objects
     */
    public static function split_alternatives($combinedurl, &$width, &$height) {
        $urls = explode('#', $combinedurl);
        $width = 0;
        $height = 0;
        $returnurls = array();

        foreach ($urls as $url) {
            $matches = null;

            // You can specify the size as a separate part of the array like
            // #d=640x480 without actually including a url in it.
            if (preg_match('/^d=([\d]{1,4})x([\d]{1,4})$/i', $url, $matches)) {
                $width  = $matches[1];
                $height = $matches[2];
                continue;
            }

            // Can also include the ?d= as part of one of the URLs (if you use
            // more than one they will be ignored except the last).
            if (preg_match('/\?d=([\d]{1,4})x([\d]{1,4})$/i', $url, $matches)) {
                $width  = $matches[1];
                $height = $matches[2];

                // Trim from URL.
                $url = str_replace($matches[0], '', $url);
            }

            // Clean up url.
            $url = clean_param($url, PARAM_URL);
            if (empty($url)) {
                continue;
            }

            // Turn it into moodle_url object.
            $returnurls[] = new moodle_url($url);
        }

        return $returnurls;
    }

    /**
     * Returns the file extension for a URL.
     * @param moodle_url $url URL
     */
    public static function get_extension(moodle_url $url) {
        // Note: Does not use core_text (. is UTF8-safe).
        $filename = self::get_filename($url);
        $dot = strrpos($filename, '.');
        if ($dot === false) {
            return '';
        } else {
            return strtolower(substr($filename, $dot + 1));
        }
    }

    /**
     * Obtains the filename from the moodle_url.
     * @param moodle_url $url URL
     * @return string Filename only (not escaped)
     */
    public static function get_filename(moodle_url $url) {
        global $CFG;

        // Use the 'file' parameter if provided (for links created when
        // slasharguments was off). If not present, just use URL path.
        $path = $url->get_param('file');
        if (!$path) {
            $path = $url->get_path();
        }

        // Remove everything before last / if present. Does not use textlib as / is UTF8-safe.
        $slash = strrpos($path, '/');
        if ($slash !== false) {
            $path = substr($path, $slash + 1);
        }
        return $path;
    }

    /**
     * Guesses MIME type for a moodle_url based on file extension.
     * @param moodle_url $url URL
     * @return string MIME type
     */
    public static function get_mimetype(moodle_url $url) {
        return mimeinfo('type', self::get_filename($url));
    }
}
