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
 * @package media_ableplayer
 * @category phpunit
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test script for media embedding.
 *
 * @package media_ableplayer
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_ableplayer_testcase extends advanced_testcase {

    /**
     * Pre-test setup. Preserves $CFG.
     */
    public function setUp() {
        parent::setUp();

        // Reset $CFG and $SERVER.
        $this->resetAfterTest();

        // Consistent initial setup: all players disabled.
        \core\plugininfo\media::set_enabled_plugins('ableplayer');

        // Pretend to be using Firefox browser (must support ogg for tests to work).
        core_useragent::instance(true, 'Mozilla/5.0 (X11; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0 ');
    }


    /**
     * Test that plugin is returned as enabled media plugin.
     */
    public function test_is_installed() {
        $sortorder = \core\plugininfo\media::get_enabled_plugins();
        $this->assertEquals(['ableplayer' => 'ableplayer'], $sortorder);
    }

    /**
     * Test method get_supported_extensions()
     */
    public function test_supported_extensions() {
        $nativeextensions = array_merge(file_get_typegroup('extension', 'html_video'),
            file_get_typegroup('extension', 'html_audio'));

        // Make sure that the list of extensions from the setting is filtered to HTML5 natively supported extensions.
        $player = new media_ableplayer_plugin();
        $this->assertNotEmpty($player->get_supported_extensions());
        $this->assertTrue(in_array('.mp3', $player->get_supported_extensions()));
        $this->assertEmpty(array_diff($player->get_supported_extensions(), $nativeextensions));

        // Try to set the audioextensions to something non-native (.ra) and make sure it is not returned as supported.
        set_config('audioextensions', '.mp3, .wav, .ra', 'media_ableplayer');
        $player = new media_ableplayer_plugin();
        $this->assertNotEmpty($player->get_supported_extensions());
        $this->assertTrue(in_array('.mp3', $player->get_supported_extensions()));
        $this->assertFalse(in_array('.ra', $player->get_supported_extensions()));
        $this->assertEmpty(array_diff($player->get_supported_extensions(), $nativeextensions));
    }

    /**
     * Test embedding without media filter (for example for displaying file resorce).
     */
    public function test_embed_url() {
        global $CFG;

        $url = new moodle_url('http://example.org/1.webm');

        $manager = core_media_manager::instance();
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $this->assertTrue($manager->can_embed_url($url, $embedoptions));
        $content = $manager->embed_url($url, 'Test & file', 0, 0, $embedoptions);

        $this->assertRegExp('~mediaplugin_ableplayer~', $content);
        $this->assertRegExp('~</video>~', $content);
        $this->assertRegExp('~title="Test &amp; file"~', $content);
        $this->assertRegExp('~style="max-width:' . $CFG->media_default_width . 'px;~', $content);

        // Repeat sending the specific size to the manager.
        $content = $manager->embed_url($url, 'New file', 123, 50, $embedoptions);
        $this->assertRegExp('~style="max-width:123px;~', $content);

        // Repeat without sending the size and with unchecked setting to limit the video size.
        set_config('limitsize', false, 'media_ableplayer');

        $manager = core_media_manager::instance();
        $content = $manager->embed_url($url, 'Test & file', 0, 0, $embedoptions);
        $this->assertNotRegExp('~style="max-width:~', $content);
    }

    /**
     * Test that mediaplugin filter replaces a link to the supported file with media tag.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_link() {
        global $CFG;
        $url = new moodle_url('http://example.org/some_filename.mp4');
        $text = html_writer::link($url, 'Watch this one');
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_ableplayer~', $content);
        $this->assertRegExp('~</video>~', $content);
        $this->assertRegExp('~title="Watch this one"~', $content);
        $this->assertNotRegExp('~<track\b~i', $content);
        $this->assertRegExp('~style="max-width:' . $CFG->media_default_width . 'px;~', $content);
    }

    /**
     * Test that mediaplugin filter adds player code on top of <video> tags.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_media() {
        global $CFG;
        $url = new moodle_url('http://example.org/some_filename.mp4');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<video controls="true"><source src="'.$url.'"/><source src="somethinginvalid"/>' .
            '<track src="'.$trackurl.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_ableplayer~', $content);
        $this->assertRegExp('~</video>~', $content);
        $this->assertRegExp('~title="some_filename.mp4"~', $content);
        $this->assertRegExp('~style="max-width:' . $CFG->media_default_width . 'px;~', $content);
        // Unsupported text and tracks are preserved.
        $this->assertRegExp('~Unsupported text~', $content);
        $this->assertRegExp('~<track\b~i', $content);
        // Invalid sources are removed.
        $this->assertNotRegExp('~somethinginvalid~i', $content);

        // Video with dimensions and source specified as src attribute without <source> tag.
        $text = '<video controls="true" width="123" height="35" src="'.$url.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);
        $this->assertRegExp('~mediaplugin_ableplayer~', $content);
        $this->assertRegExp('~</video>~', $content);
        $this->assertRegExp('~<source\b~', $content);
        $this->assertRegExp('~style="max-width:123px;~', $content);
        $this->assertNotRegExp('~width="~', $content);
        $this->assertNotRegExp('~height="~', $content);

        // Audio tag.
        $url = new moodle_url('http://example.org/some_filename.mp3');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<audio controls="true"><source src="'.$url.'"/><source src="somethinginvalid"/>' .
            '<track src="'.$trackurl.'">Unsupported text</audio>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_ableplayer~', $content);
        $this->assertNotRegExp('~</video>~', $content);
        $this->assertRegExp('~</audio>~', $content);
        $this->assertRegExp('~title="some_filename.mp3"~', $content);
        $this->assertRegExp('~style="max-width:' . $CFG->media_default_width . 'px;~', $content);
        // Unsupported text and tracks are preserved.
        $this->assertRegExp('~Unsupported text~', $content);
        $this->assertRegExp('~<track\b~i', $content);
        // Invalid sources are removed.
        $this->assertNotRegExp('~somethinginvalid~i', $content);
    }
}
