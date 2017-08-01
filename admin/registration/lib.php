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




//// SITE PRIVACY /////

/**
 * Site privacy: private
 */
define('HUB_SITENOTPUBLISHED', 'notdisplayed');

/**
 * Site privacy: public
 */
define('HUB_SITENAMEPUBLISHED', 'named');

/**
 * Site privacy: public and global
 */
define('HUB_SITELINKPUBLISHED', 'linked');

/**
 *
 * Site registration library
 *
 * @package   course
 * @copyright 2010 Moodle Pty Ltd (http://moodle.com)
 * @author    Jerome Mouneyrac
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registration_manager {

    const FORM_FIELDS = ['name', 'description', 'contactname', 'contactemail', 'contactphone', 'imageurl', 'privacy', 'street',
        'regioncode', 'countrycode', 'geolocation', 'contactable', 'emailalert', 'language'];

    /**
     * Automatically update the registration
     */
    public function cron() {
        if ($hub = $this->get_registeredhub()) {
            try {
                $this->update_registration($hub);
                mtrace(get_string('siteupdatedcron', 'hub', $hub->hubname));
            } catch (Exception $e) {
                $errorparam = new stdClass();
                $errorparam->errormessage = $e->getMessage();
                $errorparam->hubname = $hub->hubname;
                mtrace(get_string('errorcron', 'hub', $errorparam));
            }
        }
    }

    /**
     * @param $function
     * @param $params
     * @return mixed
     * @throws moodle_exception
     */
    public function call_moodlenet_webservice($function, $params, $allowpublic = false) {
        global $CFG;

        if (!$hub = $this->get_registeredhub()) {
            if ($allowpublic) {
                $token = 'publichub';
            } else if (has_capability('moodle/site:config', context_system::instance())) {
                throw new moodle_exception('registrationwarning', 'admin', new moodle_url('/admin/registration/index.php'));
            } else {
                throw new moodle_exception('registrationwarningcontactadmin', 'admin');
            }
        } else {
            $token = $hub->token;
        }

        if (!extension_loaded('xmlrpc')) {
            throw new moodle_exception('errorcronnoxmlrpc', 'hub');
        }

        require_once($CFG->dirroot . '/webservice/lib.php');
        require_once($CFG->dirroot . "/webservice/xmlrpc/lib.php");

        $serverurl = HUB_MOODLEORGHUBURL . "/local/hub/webservice/webservices.php";
        try {
            $xmlrpcclient = new webservice_xmlrpc_client($serverurl, $token);
            return $xmlrpcclient->call($function, $params);
        } catch (Exception $e) {
            // Function webservice_xmlrpc_client::call() can throw exception, wrap it into moodle_exception.
            throw new moodle_exception('hubwserror', 'hub', '', $e->getMessage());
        }
    }

    /**
     * @param stdClass $hub
     * @throws Exception when WS call was not successful
     */
    public function update_registration($hub) {
        global $CFG, $DB;

        if ($hub->huburl !== HUB_MOODLEORGHUBURL) {
            return;
        }

        $function = 'hub_update_site_info';
        $siteinfo = $this->get_site_info();
        $params = array('siteinfo' => $siteinfo);
        $this->call_moodlenet_webservice($function, $params);
        $DB->update_record('registration_hubs', ['id' => $hub->id, 'timemodified' => time()]);

        return;
    }

    public function register() {
        global $DB;

        $huburl = HUB_MOODLEORGHUBURL;
        $hubname = 'Moodle.net';
        $hub = $DB->get_record('registration_hubs', ['huburl' => $huburl]);
        if (!empty($hub->confirmed)) {
            // Caller of this method must make sure that site is not registered.
            throw new coding_exception('Site already registered');
        }

        if (empty($hub)) {
            // Create a new record in 'registration_hubs'.
            $hub = new stdClass();
            $hub->token = get_site_identifier();
            $hub->secret = $hub->token;
            $hub->huburl = $huburl;
            $hub->hubname = $hubname;
            $hub->confirmed = 0;
            $hub->timemodified = time();
            $hub->id = $DB->insert_record('registration_hubs', $hub);
        }

        $params = $this->get_site_info();
        $params['token'] = $hub->token;

        redirect(new moodle_url(HUB_MOODLEORGHUBURL . '/local/hub/siteregistration.php', $params));
    }

    public function unregister($unpublishalladvertisedcourses, $unpublishalluploadedcourses) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/publish/lib.php');

        $huburl = HUB_MOODLEORGHUBURL;
        if (!$hub = $this->get_registeredhub($huburl)) {
            return true;
        }

        $publicationmanager = new course_publish_manager();

        //unpublish course and unregister the site by web service

            //check if we need to unpublish courses
            //enrollable courses
            $hubcourseids = array();
            if ($unpublishalladvertisedcourses) {
                $enrollablecourses = $publicationmanager->get_publications($huburl, null, 1);
                if (!empty($enrollablecourses)) {
                    foreach ($enrollablecourses as $enrollablecourse) {
                        $hubcourseids[] = $enrollablecourse->hubcourseid;
                    }
                }
            }
            //downloadable courses
            if ($unpublishalluploadedcourses) {
                $downloadablecourses = $publicationmanager->get_publications($huburl, null, 0);
                if (!empty($downloadablecourses)) {
                    foreach ($downloadablecourses as $downloadablecourse) {
                        $hubcourseids[] = $downloadablecourse->hubcourseid;
                    }
                }
            }

            //unpublish the courses by web service
            if (!empty($hubcourseids)) {

                $function = 'hub_unregister_courses';
                $params = array('courseids' => $hubcourseids);
                try {
                    $this->call_moodlenet_webservice($function, $params);
                    //delete the published courses
                    if (!empty($enrollablecourses)) {
                        $publicationmanager->delete_hub_publications($huburl, 1);
                    }
                    if (!empty($downloadablecourses)) {
                        $publicationmanager->delete_hub_publications($huburl, 0);
                    }
                } catch (moodle_exception $e) {
                    $errormessage = $e->getMessage();
                    $errormessage .= html_writer::empty_tag('br') .
                        get_string('errorunpublishcourses', 'hub');

                    core\notification::add(get_string('unregistrationerror', 'hub', $errormessage),
                        core\output\notification::NOTIFY_ERROR);
                    return false;
                }
            }


        //course unpublish went ok, unregister the site now
            try {
                $this->call_moodlenet_webservice('hub_unregister_site', array());
            } catch (moodle_exception $e) {
                core\notification::add(get_string('unregistrationerror', 'hub', $e->getMessage()),
                    core\output\notification::NOTIFY_ERROR);
                return false;
            }

        $DB->delete_records('registration_hubs', array('huburl' => $huburl));
        return true;
    }

    /**
     * When the site register on a hub, he must call this function
     * @param object $hub where the site is registered on
     * @return integer id of the record
     */
    public function add_registeredhub($hub) {
        // TODO deprecate
        global $DB;
        $hub->timemodified = time();
        $id = $DB->insert_record('registration_hubs', $hub);
        return $id;
    }

    /**
     * When a site unregister from a hub, he must call this function
     * @param string $huburl the huburl to delete
     */
    public function delete_registeredhub($huburl) {
        global $DB;
        // TODO deprecate
        $DB->delete_records('registration_hubs', array('huburl' => $huburl));
    }

    /**
     * Get a hub on which the site is registered for a given url or token
     * Mostly use to check if the site is registered on a specific hub
     * @return object the  hub
     */
    public function get_registeredhub() {
        global $DB;

        $params = array('huburl' => HUB_MOODLEORGHUBURL);
        $params['confirmed'] = 1;
        $token = $DB->get_record('registration_hubs', $params);
        return $token;
    }

    /**
     * Get the hub which has not confirmed that the site is registered on,
     * but for which a request has been sent
     * @param string $huburl
     * @return object the  hub
     */
    public function get_unconfirmedhub($huburl) {
        global $DB;

        if ($huburl && $huburl != HUB_MOODLEORGHUBURL) {
            return null;
        }

        $params = array();
        $params['huburl'] = HUB_MOODLEORGHUBURL;
        $params['confirmed'] = 0;
        $token = $DB->get_record('registration_hubs', $params);
        return $token;
    }

    /**
     * Update a registered hub (mostly use to update the confirmation status)
     * @param object $hub the hub
     */
    public function update_registeredhub($hub) {
        global $DB;
        $hub->timemodified = time();
        $DB->update_record('registration_hubs', $hub);
    }

    /**
     * Return site information for a specific hub
     * @param string $huburl
     * @param array $defaults
     * @return array site info
     */
    public function get_site_info($huburl = null, $defaults = []) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/badgeslib.php');
        require_once($CFG->dirroot . "/course/lib.php");

        if (!empty($huburl) && $huburl !== HUB_MOODLEORGHUBURL) {
            throw new coding_exception('Only registration with Moodle.net is allowed. Support for other hubs has been removed');
        }

        $siteinfo = array();
        $cleanhuburl = clean_param(HUB_MOODLEORGHUBURL, PARAM_ALPHANUMEXT);
        foreach (self::FORM_FIELDS as $field) {
            $siteinfo[$field] = get_config('hub', 'site_'.$field.'_' . $cleanhuburl);
            if ($siteinfo[$field] === false && array_key_exists($field, $defaults)) {
                $siteinfo[$field] = $defaults[$field];
            }
        }

        // Statistical data.
        $siteinfo['courses'] = $DB->count_records('course') - 1;
        $siteinfo['users'] = $DB->count_records('user', array('deleted' => 0));
        $siteinfo['enrolments'] = $DB->count_records('role_assignments');
        $siteinfo['posts'] = $DB->count_records('forum_posts');
        $siteinfo['questions'] = $DB->count_records('question');
        $siteinfo['resources'] = $DB->count_records('resource');
        $siteinfo['badges'] = $DB->count_records_select('badge', 'status <> ' . BADGE_STATUS_ARCHIVED);
        $siteinfo['issuedbadges'] = $DB->count_records('badge_issued');
        $siteinfo['participantnumberaverage'] = average_number_of_participants();
        $siteinfo['modulenumberaverage'] = average_number_of_courses_modules();

        // Version and url.
        $siteinfo['moodleversion'] = $CFG->version;
        $siteinfo['moodlerelease'] = $CFG->release;
        $siteinfo['url'] = $CFG->wwwroot;

        // Mobile related information.
        $siteinfo['mobileservicesenabled'] = 0;
        $siteinfo['mobilenotificacionsenabled'] = 0;
        $siteinfo['registereduserdevices'] = 0;
        $siteinfo['registeredactiveuserdevices'] = 0;
        if (!empty($CFG->enablewebservices) && !empty($CFG->enablemobilewebservice)) {
            $siteinfo['mobileservicesenabled'] = 1;
            $siteinfo['registereduserdevices'] = $DB->count_records('user_devices');
            $airnotifierextpath = $CFG->dirroot . '/message/output/airnotifier/externallib.php';
            if (file_exists($airnotifierextpath)) { // Maybe some one uninstalled the plugin.
                require_once($airnotifierextpath);
                $siteinfo['mobilenotificacionsenabled'] = message_airnotifier_external::is_system_configured();
                $siteinfo['registeredactiveuserdevices'] = $DB->count_records('message_airnotifier_devices', array('enable' => 1));
            }
        }

        return $siteinfo;
    }

    /**
     * Retrieve the site privacy string matching the define value
     * @param string $privacy must match the define into moodlelib.php
     * @return string
     */
    public function get_site_privacy_string($privacy) {
        switch ($privacy) {
            case HUB_SITENOTPUBLISHED:
                $privacystring = get_string('siteprivacynotpublished', 'hub');
                break;
            case HUB_SITENAMEPUBLISHED:
                $privacystring = get_string('siteprivacypublished', 'hub');
                break;
            case HUB_SITELINKPUBLISHED:
                $privacystring = get_string('siteprivacylinked', 'hub');
                break;
        }
        if (empty($privacystring)) {
            throw new moodle_exception('unknownprivacy');
        }
        return $privacystring;
    }

}
?>
