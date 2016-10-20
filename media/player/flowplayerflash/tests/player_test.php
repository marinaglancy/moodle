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
 * Test classes for handling embedded media.
 *
 * @package media_flowplayerflash
 * @category phpunit
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test script for media embedding.
 *
 * @package media_flowplayerflash
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_flowplayerflash_testcase extends advanced_testcase {

    /**
     * Pre-test setup. Preserves $CFG.
     */
    public function setUp() {
        parent::setUp();

        // Reset $CFG and $SERVER.
        $this->resetAfterTest();

        // Consistent initial setup: all players disabled.
        \core\plugininfo\media::set_enabled_plugins('flowplayerflash');

        // Pretend to be using Firefox browser (must support ogg for tests to work).
        core_useragent::instance(true, 'Mozilla/5.0 (X11; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0 ');
    }


    /**
     * Test that plugin is returned as enabled media plugin.
     */
    public function test_is_installed() {
        $sortorder = \core\plugininfo\media::get_enabled_plugins();
        $this->assertEquals(['flowplayerflash' => 'flowplayerflash'], $sortorder);
    }

    /**
     * Test method get_supported_extensions()
     */
    public function test_supported_extensions() {
        $player = new media_flowplayerflash_plugin();
        $this->assertEquals(['.mp3', '.flv', '.f4v'], $player->get_supported_extensions());

        set_config('mp3', false, 'media_flowplayerflash');
        $this->assertEquals(['.flv', '.f4v'], $player->get_supported_extensions());

        set_config('flv', false, 'media_flowplayerflash');
        $this->assertEmpty($player->get_supported_extensions());

        set_config('mp3', true, 'media_flowplayerflash');
        $this->assertEquals(['.mp3'], $player->get_supported_extensions());
    }

    /**
     * Test embedding without media filter (for example for displaying file resorce).
     */
    public function test_embed_url() {
        global $CFG, $PAGE;

        // Embed mp3 file.
        $url = new moodle_url('http://example.org/filename.mp3');

        $manager = core_media_manager::instance();
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $this->assertTrue($manager->can_embed_url($url, $embedoptions));
        $content = $manager->embed_url($url, 'Testfile', 0, 0, $embedoptions);

        $this->assertRegExp('~mediaplugin_mp3~', $content);
        $this->assertRegExp('~add_audio_player\("core_media_mp3_.*", ' .
            '".*filename\.mp3", false\);~', $PAGE->requires->get_end_code());

        // Embed flv file.
        $url = new moodle_url('http://example.org/filename.flv');

        $manager = core_media_manager::instance();
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $this->assertTrue($manager->can_embed_url($url, $embedoptions));
        $content = $manager->embed_url($url, 'Testfile', 0, 0, $embedoptions);

        $this->assertRegExp('~mediaplugin_flv~', $content);
        $this->assertRegExp('~add_video_player\("core_media_flv_.*", ' .
            '".*filename\.flv", "' . $CFG->media_default_width .
            '", "' . $CFG->media_default_height . '", true\);~', $PAGE->requires->get_end_code());

        // Repeat sending the specific size to the manager.
        $content = $manager->embed_url($url, 'New file', 123, 50, $embedoptions);
        $this->assertRegExp('~add_video_player\("core_media_flv_.*", ' .
            '".*filename\.flv", 123, 50, false\);~', $PAGE->requires->get_end_code());
    }

    /**
     * Test that mediaplugin filter replaces a link to the supported file with media tag.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_link() {
        $url = new moodle_url('http://example.org/some_filename.flv');
        $text = html_writer::link($url, 'Watch this one');
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_flv~', $content);
    }

    /**
     * Test that mediaplugin filter adds player code on top of <video> tags.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_media() {
        global $CFG, $PAGE;
        $url = new moodle_url('http://example.org/some_filename.flv');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<video controls="true"><source src="'.$url.'"/>' .
            '<track src="'.$trackurl.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_flv~', $content);
        $this->assertRegExp('~add_video_player\("core_media_flv_.*", ' .
            '".*filename\.flv", "' . $CFG->media_default_width .
            '", "' . $CFG->media_default_height . '", true\);~', $PAGE->requires->get_end_code());
        // Video tag, unsupported text and tracks are removed.
        $this->assertNotRegExp('~</video>~', $content);
        $this->assertNotRegExp('~Unsupported text~', $content);
        $this->assertNotRegExp('~<track\b~i', $content);

        // Video with dimensions and source specified as src attribute without <source> tag.
        $text = '<video controls="true" width="123" height="35" src="'.$url.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);
        $this->assertRegExp('~mediaplugin_flv~', $content);
        $this->assertNotRegExp('~</video>~', $content);
        $this->assertRegExp('~add_video_player\("core_media_flv_.*", ' .
            '".*filename\.flv", 123, 35, false\);~', $PAGE->requires->get_end_code());

        // Audio tag.
        $url = new moodle_url('http://example.org/some_filename.mp3');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<audio controls="true"><source src="'.$url.'"/><source src="somethinginvalid"/>' .
            '<track src="'.$trackurl.'">Unsupported text</audio>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_mp3~', $content);
        $this->assertNotRegExp('~</audio>~', $content);
        $this->assertRegExp('~add_audio_player\("core_media_mp3_.*", ' .
            '".*some_filename\.mp3", true\);~', $PAGE->requires->get_end_code());
    }
}
