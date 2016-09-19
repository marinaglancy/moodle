<?php


/**
 * Base class for media players.
 *
 * Media players return embed HTML for a particular way of playing back audio
 * or video (or another file type).
 *
 * In order to make the code more lightweight, this is not a plugin type
 * (players cannot have their own settings, database tables, capabilities, etc).
 * These classes are used only by core_media_renderer in outputrenderers.php.
 * If you add a new class here (in core code) you must modify the
 * get_players_raw function in that file to include it.
 *
 * If a Moodle installation wishes to add extra player objects they can do so
 * by overriding that renderer in theme, and overriding the get_players_raw
 * function. The new player class should then of course be defined within the
 * custom theme or other suitable location, not in this file.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class core_media_player {
    /**
     * Placeholder text used to indicate where the fallback content is placed
     * within a result.
     */
    const PLACEHOLDER = '<!--FALLBACK-->';

    /**
     * Generates code required to embed the player.
     *
     * The returned code contains a placeholder comment '<!--FALLBACK-->'
     * (constant core_media_player::PLACEHOLDER) which indicates the location
     * where fallback content should be placed in the event that this type of
     * player is not supported by user browser.
     *
     * The $urls parameter includes one or more alternative media formats that
     * are supported by this player. It does not include formats that aren't
     * supported (see list_supported_urls).
     *
     * The $options array contains key-value pairs. See OPTION_xx constants
     * for documentation of standard option(s).
     *
     * @param array $urls URLs of media files
     * @param string $name Display name; '' to use default
     * @param int $width Optional width; 0 to use default
     * @param int $height Optional height; 0 to use default
     * @param array $options Options array
     * @return string HTML code for embed
     */
    public abstract function embed($urls, $name, $width, $height, $options);

    /**
     * Gets the list of file extensions supported by this media player.
     *
     * Note: This is only required for the default implementation of
     * list_supported_urls. If you override that function to determine
     * supported URLs in some way other than by extension, then this function
     * is not necessary.
     *
     * @return array Array of strings (extension not including dot e.g. 'mp3')
     */
    public function get_supported_extensions() {
        return array();
    }

    /**
     * Lists keywords that must be included in a url that can be embedded with
     * this player. Any such keywords should be added to the array.
     *
     * For example if this player supports FLV and F4V files then it should add
     * '.flv' and '.f4v' to the array. (The check is not case-sensitive.)
     *
     * Default handling calls the get_supported_extensions function and adds
     * a dot to each of those values, so players only need to override this
     * if they don't implement get_supported_extensions.
     *
     * This is used to improve performance when matching links in the media filter.
     *
     * @return array Array of keywords to add to the embeddable markers list
     */
    public function get_embeddable_markers() {
        $markers = array();
        foreach ($this->get_supported_extensions() as $extension) {
            $markers[] = '.' . $extension;
        }
        return $markers;
    }

    /**
     * Gets the ranking of this player. This is an integer used to decide which
     * player to use (after applying other considerations such as which ones
     * the user has disabled).
     *
     * Rank must be unique (no two players should have the same rank).
     *
     * Rank zero has a special meaning, indicating that this 'player' does not
     * really embed the video.
     *
     * Rank is not a user-configurable value because it needs to be defined
     * carefully in order to ensure that the embedding fallbacks actually work.
     * It might be possible to have some user options which affect rank, but
     * these would be best defined as e.g. checkboxes in settings that have
     * a particular effect on the rank of a couple of plugins, rather than
     * letting users generally alter rank.
     *
     * @return int Rank (higher is better)
     */
    public function get_rank() {
        global $CFG;

        // TODO deprecate
        $enabled = array_reverse(explode(',', $CFG->media_plugins_sortorder));

        if ($enabled && preg_match('/^media_(.*)_plugin$/', get_class($this), $matches)) {
            $pos = array_search($matches[1], $enabled);
            if ($pos !== false) {
                return $pos + 1;
            }
        }

        return -1;
    }

    /**
     * @return bool True if player is enabled
     */
    public function is_enabled() {
        global $CFG;

        // TODO deprecate?
        $enabled = explode(',', $CFG->media_plugins_sortorder);

        if ($enabled && preg_match('/^media_(.*)_plugin$/', get_class($this), $matches)) {
            return in_array($matches[1], $enabled);
        }

        return false;
    }

    /**
     * Given a list of URLs, returns a reduced array containing only those URLs
     * which are supported by this player. (Empty if none.)
     * @param array $urls Array of moodle_url
     * @param array $options Options (same as will be passed to embed)
     * @return array Array of supported moodle_url
     */
    public function list_supported_urls(array $urls, array $options = array()) {
        $extensions = $this->get_supported_extensions();
        $result = array();
        foreach ($urls as $url) {
            if (in_array(core_media_manager::instance()->get_extension($url), $extensions)) {
                $result[] = $url;
            }
        }
        return $result;
    }

    /**
     * Obtains suitable name for media. Uses specified name if there is one,
     * otherwise makes one up.
     * @param string $name User-specified name ('' if none)
     * @param array $urls Array of moodle_url used to make up name
     * @return string Name
     */
    protected function get_name($name, $urls) {
        // If there is a specified name, use that.
        if ($name) {
            return $name;
        }

        // Get filename of first URL.
        $url = reset($urls);
        $name = core_media_manager::instance()->get_filename($url);

        // If there is more than one url, strip the extension as we could be
        // referring to a different one or several at once.
        if (count($urls) > 1) {
            $name = preg_replace('~\.[^.]*$~', '', $name);
        }

        return $name;
    }

    /**
     * Compares by rank order, highest first. Used for sort functions.
     * @param core_media_player $a Player A
     * @param core_media_player $b Player B
     * @return int Negative if A should go before B, positive for vice versa
     */
    public static function compare_by_rank(core_media_player $a, core_media_player $b) {
        // TODO deprecate
        return $b->get_rank() - $a->get_rank();
    }

    /**
     * Utility function that sets width and height to defaults if not specified
     * as a parameter to the function (will be specified either if, (a) the calling
     * code passed it, or (b) the URL included it).
     * @param int $width Width passed to function (updated with final value)
     * @param int $height Height passed to function (updated with final value)
     */
    protected static function pick_video_size(&$width, &$height) {
        if (!$width) {
            if (!defined('CORE_MEDIA_VIDEO_WIDTH')) {
                // Default video width if no width is specified; some players may do something
                // more intelligent such as use real video width.
                // May be defined in config.php if required.
                define('CORE_MEDIA_VIDEO_WIDTH', 400);
            }
            if (!defined('CORE_MEDIA_VIDEO_HEIGHT')) {
                // Default video height. May be defined in config.php if required.
                define('CORE_MEDIA_VIDEO_HEIGHT', 300);
            }
            $width = CORE_MEDIA_VIDEO_WIDTH;
            $height = CORE_MEDIA_VIDEO_HEIGHT;
        }
    }
}
