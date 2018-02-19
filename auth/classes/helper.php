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
 * Contains helper class for auth.
 *
 * @package     core_auth
 * @copyright   2018 Mihail Geshoski <mihail@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_auth;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for auth.
 *
 * @copyright 2018 Mihail Geshoski <mihail@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Parse the agedigitalconsentmap setting into an array.
     *
     * @return array $ageconsentmapparsed
     */
    public static function parse_age_digital_consent_map() {
        global $CFG;

        $ageconsentmapparsed = array();

        $ageconsentmap = $CFG->agedigitalconsentmap;
        $countries = get_string_manager()->get_list_of_countries();
        $isdefaultvaluepresent = false;
        $lines = preg_split( '/\r\n|\r|\n/', $ageconsentmap);
        foreach ($lines as $line) {
            $arr = explode(" ", $line);
            // Check if default.
            if ($arr[0] == "*") {
                $isdefaultvaluepresent = true;
            }
            // Handle if the presented value for country is not valid.
            if ($arr[0] !== "*" && !array_key_exists($arr[0], $countries)) {
                throw new \moodle_exception('agedigitalconsentmapinvalid');
            }
            // Handle if the presented value for age is not valid.
            if (!is_numeric($arr[1]) || $arr[1] < 0 && $arr[1] !== round($arr[1], 0)) {
                throw new \moodle_exception('agedigitalconsentmapinvalid');
            }
            $ageconsentmapparsed[$arr[0]] = $arr[1];
        }
        // Handle if a default value does not exist.
        if (!$isdefaultvaluepresent) {
            throw new \moodle_exception('agedigitalconsentmapinvalid');
        }

        return $ageconsentmapparsed;
    }
}
