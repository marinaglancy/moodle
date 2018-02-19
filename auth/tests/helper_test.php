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
 * Unit tests for core_auth\helper.
 *
 * @package    core_auth
 * @copyright  2018 Mihail Geshoski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Auth helper testcase.
 *
 * @package    core_auth
 * @copyright  2018 Mihail Geshoski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_auth_helper_testcase extends advanced_testcase {

    public function test_parse_age_digital_consent_map_valid_format() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has a valid format.
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            'AT 14',
            'BE 13'
        ]);

        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $ageconsentmapparsed = \core_auth\helper::parse_age_digital_consent_map();

        $this->assertEquals([
            '*' => 16,
            'AT' => 14,
            'BE' => 13
            ], $ageconsentmapparsed
        );
    }

    public function test_parse_age_digital_consent_map_invalid_format_missing_spaces() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (missing space separator between values).
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            'AT14',
        ]);

        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_missing_newline() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (missing newline separator between sets of values).
        $agedigitalconsentmap = implode([
            '* 16',
            'AT 14'
        ]);

        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_missing_default_value() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (missing default value).
        $agedigitalconsentmap = implode(PHP_EOL, [
            'BE 16',
            'AT 14'
        ]);

        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_invalid_country() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (invalid value for country).
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            'TEST 14'
        ]);
        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_invalid_age_string() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (string value for age).
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            'AT ten'
        ]);
        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_invalid_age_negative() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (negative value for age).
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            'AT -10'
        ]);
        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_missing_age() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (missing value for age).
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            'AT '
        ]);
        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }

    public function test_parse_age_digital_consent_map_invalid_format_missing_country() {
        global $CFG;
        $this->resetAfterTest();

        // Value of agedigitalconsentmap has an invalid format (missing value for country).
        $agedigitalconsentmap = implode(PHP_EOL, [
            '* 16',
            ' 12'
        ]);
        $CFG->agedigitalconsentmap = $agedigitalconsentmap;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('agedigitalconsentmapinvalid', 'error'));

        \core_auth\helper::parse_age_digital_consent_map();
    }
}
