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

/**
 * Form for community search
 *
 * @package    block_community
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/publish/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');

class community_hub_search_form extends moodleform {

    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
        $mform = & $this->_form;

        //set default value
        $search = $this->_customdata['search'];
        if (isset($this->_customdata['coverage'])) {
            $coverage = $this->_customdata['coverage'];
        } else {
            $coverage = 'all';
        }
        if (isset($this->_customdata['licence'])) {
            $licence = $this->_customdata['licence'];
        } else {
            $licence = 'all';
        }
        if (isset($this->_customdata['subject'])) {
            $subject = $this->_customdata['subject'];
        } else {
            $subject = 'all';
        }
        if (isset($this->_customdata['audience'])) {
            $audience = $this->_customdata['audience'];
        } else {
            $audience = 'all';
        }
        if (isset($this->_customdata['language'])) {
            $language = $this->_customdata['language'];
        } else {
            $language = current_language();
        }
        if (isset($this->_customdata['educationallevel'])) {
            $educationallevel = $this->_customdata['educationallevel'];
        } else {
            $educationallevel = 'all';
        }
        if (isset($this->_customdata['downloadable'])) {
            $downloadable = $this->_customdata['downloadable'];
        } else {
            $downloadable = 1;
        }
        if (isset($this->_customdata['orderby'])) {
            $orderby = $this->_customdata['orderby'];
        } else {
            $orderby = 'newest';
        }

        $mform->addElement('header', 'site', get_string('search', 'block_community'));

        //add the course id (of the context)
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'executesearch', 1);
        $mform->setType('executesearch', PARAM_INT);

            $hubdescription = 'Moodle.net (previously known as MOOCH) connects you with free content and courses shared by Moodle users all over the world.  It contains: 

* courses you can download and use
* courses you can enrol in and participate
* other content you can import into your own courses';

                $hubname = 'Moodle.net';

                    // Hub logo.
                    $imgurl = 'https://hubdirectory.moodle.org/local/hubdirectory/webservice/download.php?hubid=62&filetype=hubscreenshot'; // TODO
                    $ascreenshothtml = html_writer::empty_tag('img', array('src' => $imgurl, 'alt' => $hubname));
                    $smalllogohtml = html_writer::empty_tag('img', array('src' => $imgurl, 'alt' => $hubname
                                    , 'height' => 30, 'width' => 40));
                    $hubimage = html_writer::tag('div', $ascreenshothtml, array('class' => 'hubimage'));

                    // hub name link + hub description.
                    $hubnamelink = html_writer::link(HUB_MOODLEORGHUBURL, html_writer::tag('h2',$hubname),
                                    array('class' => 'hubtitlelink'));
                    // The description can come from the hub directory - need to clean.
                    $hubdescription = clean_param($hubdescription, PARAM_TEXT);
                    $hubdescriptiontext = html_writer::tag('div', format_text($hubdescription, FORMAT_PLAIN),
                                    array('class' => 'hubdescription'));

                    $hubtext = html_writer::tag('div', $hubdescriptiontext, array('class' => 'hubtext'));

                    $hubimgandtext = html_writer::tag('div', $hubimage . $hubtext, array('class' => 'hubimgandtext'));

                    $hubfulldesc = html_writer::tag('div', $hubnamelink . $hubimgandtext, array('class' => 'hubmainhmtl'));

                // Add hub to the hub items.
                $hubinfo = new stdClass();
                $hubinfo->mainhtml = $hubfulldesc;
                $hubinfo->rowhtml = html_writer::tag('div', $smalllogohtml , array('class' => 'hubsmalllogo')) . $hubname;
                $hubitems[HUB_MOODLEORGHUBURL] = $hubinfo;


            // Hub listing form element.
            $mform->addElement('listing','huburl', '', '', array('items' => $hubitems,
                'showall' => get_string('showall', 'block_community'),
                'hideall' => get_string('hideall', 'block_community')));
            $mform->setDefault('huburl', HUB_MOODLEORGHUBURL);

            //display enrol/download select box if the USER has the download capability on the course
            if (has_capability('moodle/community:download',
                            context_course::instance($this->_customdata['courseid']))) {
                $options = array(0 => get_string('enrollable', 'block_community'),
                    1 => get_string('downloadable', 'block_community'));
                $mform->addElement('select', 'downloadable', get_string('enroldownload', 'block_community'),
                        $options);
                $mform->addHelpButton('downloadable', 'enroldownload', 'block_community');

                $mform->setDefault('downloadable', $downloadable);
            } else {
                $mform->addElement('hidden', 'downloadable', 0);
            }
            $mform->setType('downloadable', PARAM_INT);

            $options = array();
            $options['all'] = get_string('any');
            $options[HUB_AUDIENCE_EDUCATORS] = get_string('audienceeducators', 'hub');
            $options[HUB_AUDIENCE_STUDENTS] = get_string('audiencestudents', 'hub');
            $options[HUB_AUDIENCE_ADMINS] = get_string('audienceadmins', 'hub');
            $mform->addElement('select', 'audience', get_string('audience', 'block_community'), $options);
            $mform->setDefault('audience', $audience);
            unset($options);
            $mform->addHelpButton('audience', 'audience', 'block_community');

            $options = array();
            $options['all'] = get_string('any');
            $options[HUB_EDULEVEL_PRIMARY] = get_string('edulevelprimary', 'hub');
            $options[HUB_EDULEVEL_SECONDARY] = get_string('edulevelsecondary', 'hub');
            $options[HUB_EDULEVEL_TERTIARY] = get_string('eduleveltertiary', 'hub');
            $options[HUB_EDULEVEL_GOVERNMENT] = get_string('edulevelgovernment', 'hub');
            $options[HUB_EDULEVEL_ASSOCIATION] = get_string('edulevelassociation', 'hub');
            $options[HUB_EDULEVEL_CORPORATE] = get_string('edulevelcorporate', 'hub');
            $options[HUB_EDULEVEL_OTHER] = get_string('edulevelother', 'hub');
            $mform->addElement('select', 'educationallevel',
                    get_string('educationallevel', 'block_community'), $options);
            $mform->setDefault('educationallevel', $educationallevel);
            unset($options);
            $mform->addHelpButton('educationallevel', 'educationallevel', 'block_community');

            $publicationmanager = new course_publish_manager();
            $options = $publicationmanager->get_sorted_subjects();
            $mform->addElement('searchableselector', 'subject', get_string('subject', 'block_community'),
                    $options, array('id' => 'communitysubject'));
            $mform->setDefault('subject', $subject);
            unset($options);
            $mform->addHelpButton('subject', 'subject', 'block_community');

            require_once($CFG->libdir . "/licenselib.php");
            $licensemanager = new license_manager();
            $licences = $licensemanager->get_licenses();
            $options = array();
            $options['all'] = get_string('any');
            foreach ($licences as $license) {
                $options[$license->shortname] = get_string($license->shortname, 'license');
            }
            $mform->addElement('select', 'licence', get_string('licence', 'block_community'), $options);
            unset($options);
            $mform->addHelpButton('licence', 'licence', 'block_community');
            $mform->setDefault('licence', $licence);

            $languages = get_string_manager()->get_list_of_languages();
            core_collator::asort($languages);
            $languages = array_merge(array('all' => get_string('any')), $languages);
            $mform->addElement('select', 'language', get_string('language'), $languages);

            $mform->setDefault('language', $language);
            $mform->addHelpButton('language', 'language', 'block_community');

            $mform->addElement('select', 'orderby', get_string('orderby', 'block_community'),
                array('newest' => get_string('orderbynewest', 'block_community'),
                    'eldest' => get_string('orderbyeldest', 'block_community'),
                    'fullname' => get_string('orderbyname', 'block_community'),
                    'publisher' => get_string('orderbypublisher', 'block_community'),
                    'ratingaverage' => get_string('orderbyratingaverage', 'block_community')));

            $mform->setDefault('orderby', $orderby);
            $mform->addHelpButton('orderby', 'orderby', 'block_community');
            $mform->setType('orderby', PARAM_ALPHA);

            $mform->setAdvanced('audience');
            $mform->setAdvanced('educationallevel');
            $mform->setAdvanced('subject');
            $mform->setAdvanced('licence');
            $mform->setAdvanced('language');
            $mform->setAdvanced('orderby');

            $mform->addElement('text', 'search', get_string('keywords', 'block_community'),
                array('size' => 30));
            $mform->addHelpButton('search', 'keywords', 'block_community');
            $mform->setType('search', PARAM_NOTAGS);

            $mform->addElement('submit', 'submitbutton', get_string('search', 'block_community'));

    }

}
