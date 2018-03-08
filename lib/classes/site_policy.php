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
 * Site policy management class.
 *
 * @package    core
 * @copyright  2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Site policy management class.
 *
 * Setting $CFG->sitepolicyhandler may be set to the name of a plugin implementing callback 'site_policy_handler'
 *
 * Example of the implementation:
 *
 * function tool_policy_site_policy_handler($action) {
 *     global $USER, $DB;
 *     if ($action === core_site_policy::ACTION_REDIRECT_URL_GUEST) {
 *         return null;
 *     } else if ($action === core_site_policy::ACTION_REDIRECT_URL) {
 *         return new moodle_url('/admin/tool/policy/index.php');
 *     } else if ($action === core_site_policy::ACTION_EMBED_URL) {
 *         return new moodle_url('/admin/tool/policy/view.php');
 *     } else if ($action === core_site_policy::ACTION_EMBED_URL_GUEST) {
 *         return new moodle_url('/admin/tool/policy/viewguest.php');
 *     } else if ($action === core_site_policy::ACTION_ACCEPT) {
 *         $USER->policyagreed = 1;
 *         $DB->set_field('user', 'policyagreed', 1, array('id' => $USER->id));
 *     } else {
 *         throw new coding_exception('Action not implemented');
 *     }
 *  }
 *
 * @package    core
 * @copyright  2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_site_policy {

    const ACTION_REDIRECT_URL = 'redirect';
    const ACTION_REDIRECT_URL_GUEST = 'redirectguest';
    const ACTION_EMBED_URL = 'view';
    const ACTION_EMBED_URL_GUEST = 'viewguest';
    const ACTION_ACCEPT = 'accept';

    /**
     * Checks if the site has site policy defined
     *
     * @param bool $forguests
     * @return bool
     */
    public static function is_defined($forguests = false) {
        global $CFG;
        if (!empty($CFG->sitepolicyhandler)) {
            $url = component_callback($CFG->sitepolicyhandler, 'site_policy_handler',
                [$forguests ? self::ACTION_REDIRECT_URL_GUEST : self::ACTION_REDIRECT_URL]);
            return !empty($url);
        } else if (!$forguests) {
            return !empty($CFG->sitepolicy);
        } else {
            return !empty($CFG->sitepolicyguest);
        }
    }

    /**
     * Returns URL to redirect user to when user needs to agree to site policy
     *
     * This is a regular interactive page for web users. It should have normal Moodle header/footers, it should
     * allow user to view policies and accept them.
     *
     * @param bool $forguests
     * @return moodle_url|null (returns null if site policy is not defined)
     */
    public static function get_redirect_url($forguests = false) {
        global $CFG;
        if (!empty($CFG->sitepolicyhandler)) {
            try {
                if ($url = component_callback($CFG->sitepolicyhandler, 'site_policy_handler',
                    [$forguests ? self::ACTION_REDIRECT_URL_GUEST : self::ACTION_REDIRECT_URL])) {
                    return ($url instanceof moodle_url) ? $url : new moodle_url($url);
                }
            } catch (Exception $e) {
                debugging('Error while trying to execute the site_policy_handler callback!');
            }
        } else if ($forguests && !empty($CFG->sitepolicyguest)) {
            return new moodle_url('/user/policy.php');
        } else if (!$forguests && !empty($CFG->sitepolicy)) {
            return new moodle_url('/user/policy.php');
        }
        return null;
    }

    /**
     * Returns URL of the site policy that needs to be displayed to the user (inside iframe or to use in WS such as mobile app)
     *
     * This page should not have any header/footer, it does not also have any buttons/checkboxes. The caller needs to implement
     * the "Accept" button and call {@link self::accept()} on completion.
     *
     * @param bool $forguests
     * @return moodle_url|null
     */
    public static function get_embed_url($forguests = false) {
        global $CFG;
        if (!empty($CFG->sitepolicyhandler)) {
            if ($url = component_callback($CFG->sitepolicyhandler, 'site_policy_handler',
                    [$forguests ? self::ACTION_EMBED_URL_GUEST : self::ACTION_EMBED_URL])) {
                return ($url instanceof moodle_url) ? $url : new moodle_url($url);
            }
        } else if ($forguests && !empty($CFG->sitepolicyguest)) {
            return new moodle_url($CFG->sitepolicyguest);
        } else if (!$forguests && !empty($CFG->sitepolicy)) {
            return new moodle_url($CFG->sitepolicy);
        }
        return null;
    }

    /**
     * Accept site policy for the current user
     *
     * @return bool - false if sitepolicy not defined, user is not logged in or user has already agreed to site policy;
     *     true - if we have successfully marked the user as agreed to the site policy
     * @throws moodle_exception
     */
    public static function accept() {
        global $USER, $CFG, $DB;
        if (!isloggedin()) {
            return false;
        }
        if ($USER->policyagreed) {
            return false;
        }
        if (!empty($CFG->sitepolicyhandler)) {
            component_callback($CFG->sitepolicyhandler, 'site_policy_handler', [self::ACTION_ACCEPT]);
            if (empty($USER->policyagreed)) {
                // Site policy handler must either update $USER->policyagreed or throw exception. If it does neither,
                // it means it is not properly implemented.
                throw new moodle_exception('sitepolicynotagreed', 'error', '', self::get_redirect_url());
            }
            return true;
        } else if (self::is_defined(isguestuser())) {
            if (!isguestuser()) {
                // For the guests agreement in stored in session only, for other users - in DB.
                $DB->set_field('user', 'policyagreed', 1, array('id' => $USER->id));
            }
            $USER->policyagreed = 1;
            return true;
        } else {
            return false;
        }
    }
}
