<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/*
 * @package    moodle
 * @subpackage registration
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * The forms needed by registration pages.
 */


require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');

/**
 * This form display a unregistration form.
 */
class site_unregistration_form extends moodleform {

    public function definition() {
        $mform = & $this->_form;
        $mform->addElement('header', 'site', get_string('unregister', 'hub'));

        $unregisterlabel = get_string('unregister', 'hub');
        $mform->addElement('advcheckbox', 'unpublishalladvertisedcourses', '',
                ' ' . get_string('unpublishalladvertisedcourses', 'hub'));
        $mform->setType('unpublishalladvertisedcourses', PARAM_INT);
        $mform->addElement('advcheckbox', 'unpublishalluploadedcourses', '',
                ' ' . get_string('unpublishalluploadedcourses', 'hub'));
        $mform->setType('unpublishalluploadedcourses', PARAM_INT);

        $mform->addElement('hidden', 'unregistration', 1);
        $mform->setType('unregistration', PARAM_INT);

        $this->add_action_buttons(true, $unregisterlabel);
    }

}

/**
 * The site registration form. Information will be sent to a given hub.
 */
class site_registration_form extends moodleform {

    public function definition() {
        global $CFG;

        $strrequired = get_string('required');
        $mform = & $this->_form;
        $huburl = HUB_MOODLEORGHUBURL;
        $hubname = 'Moodle.net';
        $admin = get_admin();
        $site = get_site();

        $registrationmanager = new registration_manager();
        $siteinfo = $registrationmanager->get_site_info(null, [
            'name' => format_string($site->fullname, true, array('context' => context_course::instance(SITEID))),
            'description' => $site->summary,
            'contactname' => fullname($admin, true),
            'contactemail' => $admin->email,
            'contactphone' => $admin->phone1,
            'street' => '',
            'countrycode' => $admin->country ?: $CFG->country,
            'regioncode' => '-', // Not supported yet.
            'language' => explode('_', current_language())[0],
            'geolocation' => '',
            'emailalert' => 1,

        ]);

        $mform->addElement('header', 'moodle', get_string('registrationinfo', 'hub'));

        $mform->addElement('text', 'name', get_string('sitename', 'hub'),
                array('class' => 'registration_textfield'));
        $mform->addRule('name', $strrequired, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'sitename', 'hub');

        $options = array();
        $registrationmanager = new registration_manager();
        $options[HUB_SITENOTPUBLISHED] = $registrationmanager->get_site_privacy_string(HUB_SITENOTPUBLISHED);
        $options[HUB_SITENAMEPUBLISHED] = $registrationmanager->get_site_privacy_string(HUB_SITENAMEPUBLISHED);
        $options[HUB_SITELINKPUBLISHED] = $registrationmanager->get_site_privacy_string(HUB_SITELINKPUBLISHED);
        $mform->addElement('select', 'privacy', get_string('siteprivacy', 'hub'), $options);
        $mform->setType('privacy', PARAM_ALPHA);
        $mform->addHelpButton('privacy', 'privacy', 'hub');
        unset($options);

        $mform->addElement('textarea', 'description', get_string('sitedesc', 'hub'),
                array('rows' => 8, 'cols' => 41));
        $mform->addRule('description', $strrequired, 'required', null, 'client');
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'sitedesc', 'hub');

        $languages = get_string_manager()->get_list_of_languages();
        core_collator::asort($languages);
        $mform->addElement('select', 'language', get_string('sitelang', 'hub'),
                $languages);
        $mform->setType('language', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('language', 'sitelang', 'hub');

        $mform->addElement('textarea', 'street', get_string('postaladdress', 'hub'),
                array('rows' => 4, 'cols' => 41));
        $mform->setType('street', PARAM_TEXT);
        $mform->addHelpButton('street', 'postaladdress', 'hub');

        //TODO: use the region array I generated
//        $mform->addElement('select', 'region', get_string('selectaregion'), array('-' => '-'));
//        $mform->setDefault('region', $region);
        $mform->addElement('hidden', 'regioncode', '-');
        $mform->setType('regioncode', PARAM_ALPHANUMEXT);

        $countries = ['' => ''] + get_string_manager()->get_list_of_countries();
        $mform->addElement('select', 'countrycode', get_string('sitecountry', 'hub'), $countries);
        $mform->setType('countrycode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('countrycode', 'sitecountry', 'hub');
        $mform->addRule('countrycode', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'geolocation', get_string('sitegeolocation', 'hub'),
                array('class' => 'registration_textfield'));
        $mform->setType('geolocation', PARAM_RAW);
        $mform->addHelpButton('geolocation', 'sitegeolocation', 'hub');

        $mform->addElement('text', 'contactname', get_string('siteadmin', 'hub'),
                array('class' => 'registration_textfield'));
        $mform->addRule('contactname', $strrequired, 'required', null, 'client');
        $mform->setType('contactname', PARAM_TEXT);
        $mform->addHelpButton('contactname', 'siteadmin', 'hub');

        $mform->addElement('text', 'contactphone', get_string('sitephone', 'hub'),
                array('class' => 'registration_textfield'));
        $mform->setType('contactphone', PARAM_TEXT);
        $mform->addHelpButton('contactphone', 'sitephone', 'hub');
        $mform->setForceLtr('contactphone');

        $mform->addElement('text', 'contactemail', get_string('siteemail', 'hub'),
                array('class' => 'registration_textfield'));
        $mform->addRule('contactemail', $strrequired, 'required', null, 'client');
        $mform->setType('contactemail', PARAM_EMAIL);
        $mform->addHelpButton('contactemail', 'siteemail', 'hub');

        $options = array();
        $options[0] = get_string("registrationcontactno");
        $options[1] = get_string("registrationcontactyes");
        $mform->addElement('select', 'contactable', get_string('siteregistrationcontact', 'hub'), $options);
        $mform->setType('contactable', PARAM_INT);
        $mform->addHelpButton('contactable', 'siteregistrationcontact', 'hub');
        unset($options);

        $options = array();
        $options[0] = get_string("registrationno");
        $options[1] = get_string("registrationyes");
        $mform->addElement('select', 'emailalert', get_string('siteregistrationemail', 'hub'), $options);
        $mform->setType('emailalert', PARAM_INT);
        $mform->addHelpButton('emailalert', 'siteregistrationemail', 'hub');
        unset($options);

        //TODO site logo
        $mform->addElement('hidden', 'imageurl', ''); //TODO: temporary
        $mform->setType('imageurl', PARAM_URL);

        $mform->addElement('static', 'urlstring', get_string('siteurl', 'hub'), $siteinfo['url']);
        $mform->addHelpButton('urlstring', 'siteurl', 'hub');

        $mform->addElement('static', 'versionstring', get_string('siteversion', 'hub'), $CFG->version);
        $mform->addElement('hidden', 'moodleversion', $siteinfo['moodleversion']);
        $mform->setType('moodleversion', PARAM_INT);
        $mform->addHelpButton('versionstring', 'siteversion', 'hub');

        $mform->addElement('static', 'releasestring', get_string('siterelease', 'hub'), $CFG->release);
        $mform->addElement('hidden', 'moodlerelease', $siteinfo['moodlerelease']);
        $mform->setType('moodlerelease', PARAM_TEXT);
        $mform->addHelpButton('releasestring', 'siterelease', 'hub');

        /// Display statistic that are going to be retrieve by the hub

            $mform->addElement('static', 'courseslabel', get_string('sendfollowinginfo', 'hub'),
                    " " . get_string('coursesnumber', 'hub', $siteinfo['courses']));
            $mform->addHelpButton('courseslabel', 'sendfollowinginfo', 'hub');

            $mform->addElement('static', 'userslabel', '',
                    " " . get_string('usersnumber', 'hub', $siteinfo['users']));

            $mform->addElement('static', 'roleassignmentslabel', '',
                    " " . get_string('roleassignmentsnumber', 'hub', $siteinfo['enrolments']));

            $mform->addElement('static', 'postslabel', '',
                    " " . get_string('postsnumber', 'hub', $siteinfo['posts']));

            $mform->addElement('static', 'questionslabel', '',
                    " " . get_string('questionsnumber', 'hub', $siteinfo['questions']));

            $mform->addElement('static', 'resourceslabel', '',
                    " " . get_string('resourcesnumber', 'hub', $siteinfo['resources']));

            $mform->addElement('static', 'badgeslabel', '',
                    " " . get_string('badgesnumber', 'hub', $siteinfo['badges']));

            $mform->addElement('static', 'issuedbadgeslabel', '',
                    " " . get_string('issuedbadgesnumber', 'hub', $siteinfo['issuedbadges']));

            $mform->addElement('static', 'participantnumberaveragelabel', '',
                    " " . get_string('participantnumberaverage', 'hub', $siteinfo['participantnumberaverage']));

            $mform->addElement('static', 'modulenumberaveragelabel', '',
                    " " . get_string('modulenumberaverage', 'hub', $siteinfo['modulenumberaverage']));

            $mobileservicestatus = $siteinfo['mobileservicesenabled'] ? get_string('yes') : get_string('no');
            $mform->addElement('static', 'mobileservicesenabledlabel', '',
                    " " . get_string('mobileservicesenabled', 'hub', $mobileservicestatus));

            $mobilenotificationsstatus = $siteinfo['mobilenotificacionsenabled'] ? get_string('yes') : get_string('no');
            $mform->addElement('static', 'mobilenotificacionsenabledlabel', '',
                    " " . get_string('mobilenotificacionsenabled', 'hub', $mobilenotificationsstatus));

            $mform->addElement('static', 'registereduserdeviceslabel', '',
                    " " . get_string('registereduserdevices', 'hub', $siteinfo['registereduserdevices']));

            $mform->addElement('static', 'registeredactiveuserdeviceslabel', '',
                    " " . get_string('registeredactiveuserdevices', 'hub', $siteinfo['registeredactiveuserdevices']));

        //check if it's a first registration or update
        $hubregistered = $registrationmanager->get_registeredhub($huburl);

        if (!empty($hubregistered)) {
            $buttonlabel = get_string('updatesite', 'hub',
                            !empty($hubname) ? $hubname : $huburl);
            $mform->addElement('hidden', 'update', true);
            $mform->setType('update', PARAM_BOOL);
        } else {
            $buttonlabel = get_string('registersite', 'hub',
                            !empty($hubname) ? $hubname : $huburl);
        }

        $this->add_action_buttons(false, $buttonlabel);

        $this->set_data($siteinfo);
    }

}

