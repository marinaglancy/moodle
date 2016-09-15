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
 * Classes for handling embedded media (mainly audio and video).
 *
 * These are used only from within the core media renderer.
 *
 * To embed media from Moodle code, do something like the following:
 *
 * $mediarenderer = $PAGE->get_renderer('core', 'media');
 * echo $mediarenderer->embed_url(new moodle_url('http://example.org/a.mp3'));
 *
 * You do not need to require this library file manually. Getting the renderer
 * (the first line above) requires this library file automatically.
 *
 * @package core_media
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
if (!defined('CORE_MEDIA_AUDIO_WIDTH')) {
    // Default audio width if no width is specified.
    // May be defined in config.php if required.
    define('CORE_MEDIA_AUDIO_WIDTH', 300);
}

