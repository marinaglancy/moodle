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
 * @package media_realplayer
 * @category phpunit
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test script for media embedding.
 *
 * @package media_realplayer
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_realplayer_testcase extends advanced_testcase {

    /**
     * Pre-test setup. Preserves $CFG.
     */
    public function setUp() {
        parent::setUp();

        // Reset $CFG and $SERVER.
        $this->resetAfterTest();

        // Consistent initial setup: all players disabled.
        \core\plugininfo\media::set_enabled_plugins('realplayer');

        // Pretend to be using Firefox browser (must support ogg for tests to work).
        core_useragent::instance(true, 'Mozilla/5.0 (X11; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0 ');
    }


    /**
     * Test that plugin is returned as enabled media plugin.
     */
    public function test_is_installed() {
        $sortorder = \core\plugininfo\media::get_enabled_plugins();
        $this->assertEquals(['realplayer' => 'realplayer'], $sortorder);
    }

    /**
     * Test embedding without media filter (for example for displaying file resorce).
     */
    public function test_embed_url() {
        global $CFG;

        $url = new moodle_url('http://example.org/1.rv');

        $manager = core_media_manager::instance();
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $this->assertTrue($manager->can_embed_url($url, $embedoptions));
        $content = $manager->embed_url($url, 'Test & file', 0, 0, $embedoptions);

        $this->assertRegExp('~mediaplugin_real~', $content);
        $this->assertRegExp('~</object>~', $content);
        $this->assertContains('CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA', $content);
    }

    /**
     * Test that mediaplugin filter replaces a link to the supported file with media tag.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_link() {
        global $CFG;
        $url = new moodle_url('http://example.org/some_filename.rv');
        $text = html_writer::link($url, 'Watch this one');
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_real~', $content);
        $this->assertRegExp('~</object>~', $content);
        $this->assertRegExp('~width="' . $CFG->media_default_width . '" height="' .
            $CFG->media_default_height . '"~', $content);
        $this->assertContains('CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA', $content);
    }

    /**
     * Test that mediaplugin filter adds player code on top of <video> tags.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_media() {
        $url = new moodle_url('http://example.org/some_filename.rv');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<video controls="true"><source src="'.$url.'"/>' .
            '<track src="'.$trackurl.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_real~', $content);
        $this->assertRegExp('~</object>~', $content);
        $this->assertContains('CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA', $content);
        // Video tag, unsupported text and tracks are removed.
        $this->assertNotRegExp('~</video>~', $content);
        $this->assertNotRegExp('~<source\b~', $content);
        $this->assertNotRegExp('~Unsupported text~', $content);
        $this->assertNotRegExp('~<track\b~i', $content);

        // Audio tag.
        $url = new moodle_url('http://example.org/some_filename.ra');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<audio controls="true"><source src="'.$url.'"/>' .
            '<track src="'.$trackurl.'">Unsupported text</audio>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_real~', $content);
        $this->assertRegExp('~</object>~', $content);
        $this->assertContains('CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA', $content);
        // Audio tag, unsupported text and tracks are removed.
        $this->assertNotRegExp('~</audio>~', $content);
        $this->assertNotRegExp('~<source\b~', $content);
        $this->assertNotRegExp('~Unsupported text~', $content);
        $this->assertNotRegExp('~<track\b~i', $content);
    }
}
