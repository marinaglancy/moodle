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
 * Renderer for use with the course section and all the goodness that falls
 * within it.
 *
 * This renderer should contain methods useful to courses, and categories.
 *
 * @package   moodlecore
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The core course renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','course');
 */
class core_course_renderer extends plugin_renderer_base {

    /**
     * A cache of strings
     * @var stdClass
     */
    protected $strings;

    /**
     * Override the constructor so that we can initialise the string cache
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        $this->strings = new stdClass;
        parent::__construct($page, $target);
        $this->add_modchoosertoggle();
    }

    /**
     * Adds the item in course settings navigation to toggle modchooser
     *
     * Theme can overwrite as an empty function to exclude it (for example if theme does not
     * use modchooser at all)
     */
    protected function add_modchoosertoggle() {
        global $CFG;
        static $modchoosertoggleadded = false;
        // Add the module chooser toggle to the course page
        if ($modchoosertoggleadded || $this->page->state > moodle_page::STATE_PRINTING_HEADER ||
                $this->page->course->id == SITEID ||
                !$this->page->user_is_editing() ||
                !($context = context_course::instance($this->page->course->id)) ||
                !has_capability('moodle/course:update', $context) ||
                !course_ajax_enabled($this->page->course) ||
                !($coursenode = $this->page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) ||
                !$coursenode->get('editsettings')) {
            // too late or we are on site page or we could not find the course settings node
            // or we are not allowed to edit
            return;
        }
        $modchoosertoggleadded = true;
        if ($this->page->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
            // We are on the course page, retain the current page params e.g. section.
            $modchoosertoggleurl = clone($this->page->url);
        } else {
            // Edit on the main course page.
            $modchoosertoggleurl = new moodle_url('/course/view.php', array('id' => $this->page->course->id,
                'return' => $this->page->url->out_as_local_url(false)));
        }
        $modchoosertoggleurl->param('sesskey', sesskey());
        if ($usemodchooser = get_user_preferences('usemodchooser', $CFG->modchooserdefault)) {
            $modchoosertogglestring = get_string('modchooserdisable', 'moodle');
            $modchoosertoggleurl->param('modchooser', 'off');
        } else {
            $modchoosertogglestring = get_string('modchooserenable', 'moodle');
            $modchoosertoggleurl->param('modchooser', 'on');
        }
        $modchoosertoggle = navigation_node::create($modchoosertogglestring, $modchoosertoggleurl, navigation_node::TYPE_SETTING);
        $coursenode->add_node($modchoosertoggle, 'editsettings');
        $modchoosertoggle->add_class('modchoosertoggle');
        $modchoosertoggle->add_class('visibleifjs');
        user_preference_allow_ajax_update('usemodchooser', PARAM_BOOL);
    }

    /**
     * Renders course info box.
     *
     * @param stdClass $course
     * @return string
     */
    public function course_info_box(stdClass $course) {
        global $CFG;

        $context = context_course::instance($course->id);

        $content = '';
        $content .= $this->output->box_start('generalbox info');

        $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
        $content .= format_text($summary, $course->summaryformat, array('overflowdiv'=>true), $course->id);
        if (!empty($CFG->coursecontact)) {
            $coursecontactroles = explode(',', $CFG->coursecontact);
            foreach ($coursecontactroles as $roleid) {
                if ($users = get_role_users($roleid, $context, true, '', null, false)) {
                    foreach ($users as $teacher) {
                        $role = new stdClass();
                        $role->id = $teacher->roleid;
                        $role->name = $teacher->rolename;
                        $role->shortname = $teacher->roleshortname;
                        $role->coursealias = $teacher->rolecoursealias;
                        $fullname = fullname($teacher, has_capability('moodle/site:viewfullnames', $context));
                        $namesarray[] = role_get_name($role, $context).': <a href="'.$CFG->wwwroot.'/user/view.php?id='.
                            $teacher->id.'&amp;course='.SITEID.'">'.$fullname.'</a>';
                    }
                }
            }

            if (!empty($namesarray)) {
                $content .= "<ul class=\"teachers\">\n<li>";
                $content .= implode('</li><li>', $namesarray);
                $content .= "</li></ul>";
            }
        }

        $content .= $this->output->box_end();

        return $content;
    }

    /**
     * Renderers a structured array of courses and categories into a nice
     * XHTML tree structure.
     *
     * This method was designed initially to display the front page course/category
     * combo view. The structure can be retrieved by get_course_category_tree()
     *
     * @param array $structure
     * @return string
     */
    public function course_category_tree(array $structure) {
        $this->strings->summary = get_string('summary');

        // Generate an id and the required JS call to make this a nice widget
        $id = html_writer::random_id('course_category_tree');
        $this->page->requires->js_init_call('M.util.init_toggle_class_on_click', array($id, '.category.with_children .category_label', 'collapsed', '.category.with_children'));

        // Start content generation
        $content = html_writer::start_tag('div', array('class'=>'course_category_tree', 'id'=>$id));
        foreach ($structure as $category) {
            $content .= $this->course_category_tree_category($category);
        }
        $content .= html_writer::start_tag('div', array('class'=>'controls'));
        $content .= html_writer::tag('div', get_string('collapseall'), array('class'=>'addtoall expandall'));
        $content .= html_writer::tag('div', get_string('expandall'), array('class'=>'removefromall collapseall'));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');

        // Return the course category tree HTML
        return $content;
    }

    /**
     * Renderers a category for use with course_category_tree
     *
     * @param array $category
     * @param int $depth
     * @return string
     */
    protected function course_category_tree_category(stdClass $category, $depth=1) {
        $content = '';
        $hassubcategories = (isset($category->categories) && count($category->categories)>0);
        $hascourses = (isset($category->courses) && count($category->courses)>0);
        $classes = array('category');
        if ($category->parent != 0) {
            $classes[] = 'subcategory';
        }
        if (empty($category->visible)) {
            $classes[] = 'dimmed_category';
        }
        if ($hassubcategories || $hascourses) {
            $classes[] = 'with_children';
            if ($depth > 1) {
                $classes[] = 'collapsed';
            }
        }
        $categoryname = format_string($category->name, true, array('context' => context_coursecat::instance($category->id)));

        $content .= html_writer::start_tag('div', array('class'=>join(' ', $classes)));
        $content .= html_writer::start_tag('div', array('class'=>'category_label'));
        $content .= html_writer::link(new moodle_url('/course/category.php', array('id'=>$category->id)), $categoryname, array('class'=>'category_link'));
        $content .= html_writer::end_tag('div');
        if ($hassubcategories) {
            $content .= html_writer::start_tag('div', array('class'=>'subcategories'));
            foreach ($category->categories as $subcategory) {
                $content .= $this->course_category_tree_category($subcategory, $depth+1);
            }
            $content .= html_writer::end_tag('div');
        }
        if ($hascourses) {
            $content .= html_writer::start_tag('div', array('class'=>'courses'));
            $coursecount = 0;
            $strinfo = new lang_string('info');
            foreach ($category->courses as $course) {
                $classes = array('course');
                $linkclass = 'course_link';
                if (!$course->visible) {
                    $linkclass .= ' dimmed';
                }
                $coursecount ++;
                $classes[] = ($coursecount%2)?'odd':'even';
                $content .= html_writer::start_tag('div', array('class'=>join(' ', $classes)));
                $content .= html_writer::link(new moodle_url('/course/view.php', array('id'=>$course->id)), format_string($course->fullname), array('class'=>$linkclass));
                $content .= html_writer::start_tag('div', array('class'=>'course_info clearfix'));

                // print enrol info
                if ($icons = enrol_get_course_info_icons($course)) {
                    foreach ($icons as $pix_icon) {
                        $content .= $this->render($pix_icon);
                    }
                }

                if ($course->summary) {
                    $url = new moodle_url('/course/info.php', array('id' => $course->id));
                    $image = html_writer::empty_tag('img', array('src'=>$this->output->pix_url('i/info'), 'alt'=>$this->strings->summary));
                    $content .= $this->action_link($url, $image, new popup_action('click', $url, 'courseinfo'), array('title' => $this->strings->summary));
                }
                $content .= html_writer::end_tag('div');
                $content .= html_writer::end_tag('div');
            }
            $content .= html_writer::end_tag('div');
        }
        $content .= html_writer::end_tag('div');
        return $content;
    }

    /**
     * Build the HTML for the module chooser javascript popup
     *
     * @param array $modules A set of modules as returned form @see
     * get_module_metadata
     * @param object $course The course that will be displayed
     * @return string The composed HTML for the module
     */
    public function course_modchooser($modules, $course) {
        static $isdisplayed = false;
        if ($isdisplayed) {
            return '';
        }
        $isdisplayed = true;

        // Add the module chooser
        $this->page->requires->yui_module('moodle-course-modchooser',
        'M.course.init_chooser',
        array(array('courseid' => $course->id, 'closeButtonTitle' => get_string('close', 'editor')))
        );
        $this->page->requires->strings_for_js(array(
                'addresourceoractivity',
                'modchooserenable',
                'modchooserdisable',
        ), 'moodle');

        // Add the header
        $header = html_writer::tag('div', get_string('addresourceoractivity', 'moodle'),
                array('class' => 'hd choosertitle'));

        $formcontent = html_writer::start_tag('form', array('action' => new moodle_url('/course/jumpto.php'),
                'id' => 'chooserform', 'method' => 'post'));
        $formcontent .= html_writer::start_tag('div', array('id' => 'typeformdiv'));
        $formcontent .= html_writer::tag('input', '', array('type' => 'hidden', 'id' => 'course',
                'name' => 'course', 'value' => $course->id));
        $formcontent .= html_writer::tag('input', '',
                array('type' => 'hidden', 'class' => 'jump', 'name' => 'jump', 'value' => ''));
        $formcontent .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'sesskey',
                'value' => sesskey()));
        $formcontent .= html_writer::end_tag('div');

        // Put everything into one tag 'options'
        $formcontent .= html_writer::start_tag('div', array('class' => 'options'));
        $formcontent .= html_writer::tag('div', get_string('selectmoduletoviewhelp', 'moodle'),
                array('class' => 'instruction'));
        // Put all options into one tag 'alloptions' to allow us to handle scrolling
        $formcontent .= html_writer::start_tag('div', array('class' => 'alloptions'));

         // Activities
        $activities = array_filter($modules, function($mod) {
            return ($mod->archetype !== MOD_ARCHETYPE_RESOURCE && $mod->archetype !== MOD_ARCHETYPE_SYSTEM);
        });
        if (count($activities)) {
            $formcontent .= $this->course_modchooser_title('activities');
            $formcontent .= $this->course_modchooser_module_types($activities);
        }

        // Resources
        $resources = array_filter($modules, function($mod) {
            return ($mod->archetype === MOD_ARCHETYPE_RESOURCE);
        });
        if (count($resources)) {
            $formcontent .= $this->course_modchooser_title('resources');
            $formcontent .= $this->course_modchooser_module_types($resources);
        }

        $formcontent .= html_writer::end_tag('div'); // modoptions
        $formcontent .= html_writer::end_tag('div'); // types

        $formcontent .= html_writer::start_tag('div', array('class' => 'submitbuttons'));
        $formcontent .= html_writer::tag('input', '',
                array('type' => 'submit', 'name' => 'submitbutton', 'class' => 'submitbutton', 'value' => get_string('add')));
        $formcontent .= html_writer::tag('input', '',
                array('type' => 'submit', 'name' => 'addcancel', 'class' => 'addcancel', 'value' => get_string('cancel')));
        $formcontent .= html_writer::end_tag('div');
        $formcontent .= html_writer::end_tag('form');

        // Wrap the whole form in a div
        $formcontent = html_writer::tag('div', $formcontent, array('id' => 'chooseform'));

        // Put all of the content together
        $content = $formcontent;

        $content = html_writer::tag('div', $content, array('class' => 'choosercontainer'));
        return $header . html_writer::tag('div', $content, array('class' => 'chooserdialoguebody'));
    }

    /**
     * Build the HTML for a specified set of modules
     *
     * @param array $modules A set of modules as used by the
     * course_modchooser_module function
     * @return string The composed HTML for the module
     */
    protected function course_modchooser_module_types($modules) {
        $return = '';
        foreach ($modules as $module) {
            if (!isset($module->types)) {
                $return .= $this->course_modchooser_module($module);
            } else {
                $return .= $this->course_modchooser_module($module, array('nonoption'));
                foreach ($module->types as $type) {
                    $return .= $this->course_modchooser_module($type, array('option', 'subtype'));
                }
            }
        }
        return $return;
    }

    /**
     * Return the HTML for the specified module adding any required classes
     *
     * @param object $module An object containing the title, and link. An
     * icon, and help text may optionally be specified. If the module
     * contains subtypes in the types option, then these will also be
     * displayed.
     * @param array $classes Additional classes to add to the encompassing
     * div element
     * @return string The composed HTML for the module
     */
    protected function course_modchooser_module($module, $classes = array('option')) {
        $output = '';
        $output .= html_writer::start_tag('div', array('class' => implode(' ', $classes)));
        $output .= html_writer::start_tag('label', array('for' => 'module_' . $module->name));
        if (!isset($module->types)) {
            $output .= html_writer::tag('input', '', array('type' => 'radio',
                    'name' => 'jumplink', 'id' => 'module_' . $module->name, 'value' => $module->link));
        }

        $output .= html_writer::start_tag('span', array('class' => 'modicon'));
        if (isset($module->icon)) {
            // Add an icon if we have one
            $output .= $module->icon;
        }
        $output .= html_writer::end_tag('span');

        $output .= html_writer::tag('span', $module->title, array('class' => 'typename'));
        if (!isset($module->help)) {
            // Add help if found
            $module->help = get_string('nohelpforactivityorresource', 'moodle');
        }

        // Format the help text using markdown with the following options
        $options = new stdClass();
        $options->trusted = false;
        $options->noclean = false;
        $options->smiley = false;
        $options->filter = false;
        $options->para = true;
        $options->newlines = false;
        $options->overflowdiv = false;
        $module->help = format_text($module->help, FORMAT_MARKDOWN, $options);
        $output .= html_writer::tag('span', $module->help, array('class' => 'typesummary'));
        $output .= html_writer::end_tag('label');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    protected function course_modchooser_title($title, $identifier = null) {
        $module = new stdClass();
        $module->name = $title;
        $module->types = array();
        $module->title = get_string($title, $identifier);
        $module->help = '';
        return $this->course_modchooser_module($module, array('moduletypetitle'));
    }

    /**
     * Renders HTML for displaying the sequence of course module editing buttons
     *
     * @see course_get_cm_edit_actions()
     *
     * @param array $actions array of action_link or pix_icon objects
     * @return string
     */
    public function course_section_cm_edit_actions($actions) {
        $output = html_writer::start_tag('span', array('class' => 'commands'));
        foreach ($actions as $action) {
            if ($action instanceof renderable) {
                $output .= $this->output->render($action);
            } else {
                $output .= $action;
            }
        }
        $output .= html_writer::end_tag('span');
        return $output;
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * Note, if theme overwrites this function and it does not use modchooser,
     * see also {@link core_course_renderer::add_modchoosertoggle()}
     *
     * @param stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     * @return string
     */
    function course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $CFG;

        $vertical = !empty($displayoptions['inblock']);

        // check to see if user can add menus and there are modules to add
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
                || !$this->page->user_is_editing()
                || !($modnames = get_module_types_names()) || empty($modnames)) {
            return '';
        }

        // Retrieve all modules with associated metadata
        $modules = get_module_metadata($course, $modnames, $sectionreturn);
        $urlparams = array('section' => $section);

        // We'll sort resources and activities into two lists
        $activities = array(MOD_CLASS_ACTIVITY => array(), MOD_CLASS_RESOURCE => array());

        foreach ($modules as $module) {
            if (!array_key_exists($module->archetype, $activities)) {
                // System modules cannot be added by user, do not add to dropdown
            } else if (isset($module->types)) {
                // This module has a subtype
                // NOTE: this is legacy stuff, module subtypes are very strongly discouraged!!
                $subtypes = array();
                foreach ($module->types as $subtype) {
                    $link = $subtype->link->out(true, $urlparams);
                    $subtypes[$link] = $subtype->title;
                }

                // Sort module subtypes into the list
                if (!empty($module->title)) {
                    // This grouping has a name
                    $activities[$module->archetype][] = array($module->title => $subtypes);
                } else {
                    // This grouping does not have a name
                    $activities[$module->archetype] = array_merge($activities[$module->archetype], $subtypes);
                }
            } else {
                // This module has no subtypes
                $link = $module->link->out(true, $urlparams);
                $activities[$module->archetype][$link] = $module->title;
            }
        }

        $straddactivity = get_string('addactivity');
        $straddresource = get_string('addresource');
        $sectionname = get_section_name($course, $section);
        $strresourcelabel = get_string('addresourcetosection', null, $sectionname);
        $stractivitylabel = get_string('addactivitytosection', null, $sectionname);

        $output = html_writer::start_tag('div', array('class' => 'section_add_menus', 'id' => 'add_menus-section-' . $section));

        if (!$vertical) {
            $output .= html_writer::start_tag('div', array('class' => 'horizontal'));
        }

        if (!empty($activities[MOD_CLASS_RESOURCE])) {
            $select = new url_select($activities[MOD_CLASS_RESOURCE], '', array(''=>$straddresource), "ressection$section");
            $select->set_help_icon('resources');
            $select->set_label($strresourcelabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!empty($activities[MOD_CLASS_ACTIVITY])) {
            $select = new url_select($activities[MOD_CLASS_ACTIVITY], '', array(''=>$straddactivity), "section$section");
            $select->set_help_icon('activities');
            $select->set_label($stractivitylabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!$vertical) {
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div');

        if (course_ajax_enabled($course) && $course->id == $this->page->course->id) {
            // modchooser can be added only for the current course set on the page!
            $straddeither = get_string('addresourceoractivity');
            // The module chooser link
            $modchooser = html_writer::start_tag('div', array('class' => 'mdl-right'));
            $modchooser.= html_writer::start_tag('div', array('class' => 'section-modchooser'));
            $icon = $this->output->pix_icon('t/add', '');
            $span = html_writer::tag('span', $straddeither, array('class' => 'section-modchooser-text'));
            $modchooser .= html_writer::tag('span', $icon . $span, array('class' => 'section-modchooser-link'));
            $modchooser.= html_writer::end_tag('div');
            $modchooser.= html_writer::end_tag('div');

            // Wrap the normal output in a noscript div
            $usemodchooser = get_user_preferences('usemodchooser', $CFG->modchooserdefault);
            if ($usemodchooser) {
                $output = html_writer::tag('div', $output, array('class' => 'hiddenifjs addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'visibleifjs addresourcemodchooser'));
            } else {
                // If the module chooser is disabled, we need to ensure that the dropdowns are shown even if javascript is disabled
                $output = html_writer::tag('div', $output, array('class' => 'show addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'hide addresourcemodchooser'));
            }
            $output = $this->course_modchooser($modules, $course) . $modchooser . $output;
        }

        return $output;
    }

    /**
     * Renders HTML to display name and summary of the remote course
     *
     * @param stdClass $course object representing remote course as returned by {@link get_my_remotecourses()}
     * @return string
     */
    function remote_course_link($course) {
        $url = new moodle_url('/auth/mnet/jump.php', array(
            'hostid' => $course->hostid,
            'wantsurl' => '/course/view.php?id='. $course->remoteid
        ));

        $output = html_writer::start_tag('div', array('class' => 'coursebox remotecoursebox clearfix'));
        $output .= html_writer::start_tag('div', array('class' => 'info'));
        $output .= html_writer::start_tag('div', array('class' => 'name'));
        $output .= html_writer::link($url, format_string($course->fullname),
                        array('title' => get_string('entercourse')))
                .'<br />'
            . format_string($course->hostname) . ' : '
            . format_string($course->cat_name) . ' : '
            . format_string($course->shortname);
        $output .= html_writer::end_tag('div'); // .name
        $output .= html_writer::end_tag('div'); // .info
        $output .= html_writer::start_tag('div', array('class' => 'summary'));
        $options = new stdClass();
        $options->noclean = true;
        $options->para = false;
        $options->overflowdiv = true;
        $output .= format_text($course->summary, $course->summaryformat, $options);
        $output .= html_writer::end_tag('div'); // .summary
        $output .= html_writer::end_tag('div'); // .coursebox.remotecoursebox
        return $output;
    }

    /**
     * Renders HTML to display link to the remote host
     *
     * @param array $host array with host attributes as returned by {@link get_my_remotehosts()}
     */
    function remote_host_link($host) {
        $output = html_writer::start_tag('div', array('class' => 'coursebox clearfix'));
        $output .= html_writer::start_tag('div', array('class' => 'info'));
        $output .= html_writer::start_tag('div', array('class' => 'name'));
        $output .= html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/mnethost'),
            'alt' => get_string('course'), 'class' => 'icon'));
        $output .= html_writer::link(s($host['url']), s($host['name']), array('title' => s($host['name'])));
        $output .= ' - '. $host['count'] . ' ' . get_string('courses');
        $output .= html_writer::end_tag('div'); // .name
        $output .= html_writer::end_tag('div'); // .info
        $output .= html_writer::end_tag('div'); // .coursebox
        return $output;
    }

    /**
     * Renders HTML to displays list of courses current user is enrolled into
     *
     * To be displayed on front page in case of setting FRONTPAGEENROLLEDCOURSELIST
     * Note this is legacy, recommended to use "My moodle"
     */
    function enrolled_courses_list() {
        global $CFG, $DB;
        $output = '';

        // get lists of local courses
        $courses  = enrol_get_my_courses('summary', 'visible DESC,sortorder ASC');
        unset($courses[SITEID]);

        if (!empty($courses)) {
            $output .= $this->courses_list($courses);
        }

        // MNET
        if (!empty($CFG->mnet_dispatcher_mode) && $CFG->mnet_dispatcher_mode==='strict') {
            $rcourses = get_my_remotecourses();
            $rhosts   = get_my_remotehosts();
            if (!empty($rcourses)) {
                // at the IDP, we know of all the remote courses
                foreach ($rcourses as $course) {
                    $output .= $this->remote_course_link($course);
                }
            } elseif (!empty($rhosts)) {
                // non-IDP, we know of all the remote servers, but not courses
                foreach ($rhosts as $host) {
                    $output .= $this->remote_host_link($host);
                }
            }
        }

        if (empty($output)) {
            // no enrolled or remote courses found, just print list of available courses
            ob_start();
            if ($DB->count_records("course_categories") > 1) {
                echo $this->output->box_start("categorybox");
                print_whole_category_list();
                echo $this->output->box_end();
            } else {
                print_courses(0);
            }
            $output .= ob_get_contents();
            ob_end_clean();
        } else {
            // enrolled and/or remote courses found but there are more courses
            // in the system, display course search box
            $morecoursesexist = $DB->count_records('course') > (count($courses) + 1);
            if ($morecoursesexist) {  // Some courses not being displayed
                $output .= "<table width=\"100%\"><tr><td align=\"center\">";
                $output .= $this->course_search_form('', 'short');
                $output .= "</td><td align=\"center\">";
                $output .= $this->output->single_button(new moodle_url('/course/index.php'),
                        get_string("fulllistofcourses"), "get");
                $output .= "</td></tr></table>\n";
            }
        }
        return $output;
    }

    /**
     * Renders HTML to display a list of links to courses
     *
     * @param array $courses array of course objects
     * @param string $highlightterms string to highlight in course name/summary
     * @param bool $displaycategory whether to display category name for each course
     * @return string
     */
    function courses_list($courses, $highlightterms = '', $displaycategory = false) {
        global $CFG;
        if ($displaycategory) {
            require_once($CFG->libdir.'/coursecatlib.php');
            // retrieve list of categories names
            $categorynames = coursecat::make_categories_list();
        }

        $output = '';
        if (!empty($courses)) {
            $output .= html_writer::start_tag('ul', array('class'=>'unlist'));
            foreach ($courses as $course) {
                $output .= html_writer::start_tag('li');
                if ($displaycategory && !empty($course->category)) {
                    $output .= $this->course_link($course, $highlightterms, true, $categorynames[$course->category]);
                } else {
                    $output .= $this->course_link($course, $highlightterms);
                }
                $output .= html_writer::end_tag('li');
            }
            $output .= html_writer::end_tag('ul');
        } else {
            $output .= $this->output->heading(get_string("nocoursesyet"));
        }
        return $output;
    }

    /**
     * Renders a description of a course, suitable for browsing in a list
     *
     * Usually is only called from {@link core_course_renderer::courses_list()}
     *
     * @param object $course the course object.
     * @param string $highlightterms (optional) some search terms that should be highlighted in the display.
     */
    function course_link($course, $highlightterms = '', $displaycategory = false, $categoryname = null) {
        // Rewrite file URLs so that they are correct
        $context = context_course::instance($course->id);
        $course->summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', NULL);

        $output = '';
        $output .= html_writer::start_tag('div', array('class'=>'coursebox clearfix'));
        $output .= html_writer::start_tag('div', array('class'=>'info'));
        $output .= html_writer::start_tag('h3', array('class'=>'name'));

        $coursename = get_course_display_name_for_list($course);
        $linktext = highlight($highlightterms, format_string($coursename));
        $linkparams = array('title'=>get_string('entercourse'));
        if (empty($course->visible)) {
            $linkparams['class'] = 'dimmed';
        }
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $output .= html_writer::link($courseurl, $linktext, $linkparams);
        $output .= html_writer::end_tag('h3'); // .name
        $coursecontacts = course_get_coursecontacts($course);
        if (!empty($coursecontacts)) {
            $output .= html_writer::start_tag('ul', array('class' => 'teachers'));
            foreach ($coursecontacts as $userid => $coursecontact) {
                $name = $coursecontact['rolename'].': '.
                        html_writer::link(new moodle_url('/user/view.php',
                                array('id' => $userid, 'course' => SITEID)),
                            $coursecontact['username']);
                $output .= html_writer::tag('li', $name);
            }
            $output .= html_writer::end_tag('ul'); // .teachers
        }
        $output .= html_writer::end_tag('div'); // .info

        $output .= html_writer::start_tag('div', array('class'=>'summary'));
        $options = new stdClass();
        $options->noclean = true;
        $options->para = false;
        $options->overflowdiv = true;
        if (!isset($course->summaryformat)) {
            $course->summaryformat = FORMAT_MOODLE;
        }
        $output .= highlight($highlightterms, format_text($course->summary, $course->summaryformat, $options,  $course->id));
        if ($icons = enrol_get_course_info_icons($course)) {
            $output .= html_writer::start_tag('div', array('class'=>'enrolmenticons'));
            foreach ($icons as $icon) {
                $icon->attributes['alt'] .= ': '. format_string($coursename, true, array('context' => $context));
                $output .= $this->output->render($icon);
            }
            $output .= html_writer::end_tag('div'); // .enrolmenticons
        }
        if ($displaycategory && $course->category) {
            $output .= html_writer::start_tag('p', array('class' => 'category'));
            $output .= get_string('category').': ';
            $output .= html_writer::link(new moodle_url('/course/category.php', array('id' => $course->category)),
                    $categoryname);
            $output .= html_writer::end_tag('p'); // .category
        }
        $output .= html_writer::end_tag('div'); // .summary
        $output .= html_writer::end_tag('div'); // .coursebox
        return $output;
    }

    /**
     * Renders HTML to display a link to crate a new course in the specified category
     *
     * @param int $categoryid
     * @return string
     */
    function create_course_link($categoryid) {
        $createcourseurl = new moodle_url('/course/edit.php', array('category' => $categoryid));
        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'addcoursebutton'));
        $output .= $this->output->single_button($createcourseurl, get_string("addnewcourse"));
        $output .= html_writer::end_tag('div'); // .addcoursebutton
        return $output;
    }

    /**
     * Renders html to display a course search form
     *
     * @param string $value default value to populate the search field
     * @param string $format display format - 'plain' (default), 'short' or 'navbar'
     * @return string
     */
    function course_search_form($value = '', $format = 'plain') {
        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }

        switch ($format) {
            case 'navbar' :
                $formid = 'coursesearchnavbar';
                $inputid = 'navsearchbox';
                $inputsize = 20;
                break;
            case 'short' :
                $inputid = 'shortsearchbox';
                $inputsize = 12;
                break;
            default :
                $inputid = 'coursesearchbox';
                $inputsize = 30;
        }

        $strsearchcourses= get_string("searchcourses");
        $searchurl = new moodle_url('/course/search.php');

        $output = html_writer::start_tag('form', array('id' => $formid, 'action' => $searchurl, 'method' => 'get'));
        $output .= html_writer::start_tag('fieldset', array('class' => 'coursesearchbox invisiblefieldset'));
        $output .= html_writer::tag('lavel', $strsearchcourses.': ', array('for' => $inputid));
        $output .= html_writer::empty_tag('input', array('type' => 'text', 'id' => $inputid,
            'size' => $inputsize, 'name' => 'search', 'value' => s($value)));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'value' => get_string('go')));
        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     */
    public function course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = array()) {
        global $CFG;
        $output = '';
        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return $output;
        }
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);
        if ($completion == COMPLETION_TRACKING_NONE) {
            return $output;
        }

        $completiondata = $completioninfo->get_data($mod, true);
        $completionicon = '';

        if ($this->page->user_is_editing()) {
            switch ($completion) {
                case COMPLETION_TRACKING_MANUAL :
                    $completionicon = 'manual-enabled'; break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    $completionicon = 'auto-enabled'; break;
            }
        } else if ($completion == COMPLETION_TRACKING_MANUAL) {
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'manual-n'; break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'manual-y'; break;
            }
        } else { // Automatic
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'auto-n'; break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'auto-y'; break;
                case COMPLETION_COMPLETE_PASS:
                    $completionicon = 'auto-pass'; break;
                case COMPLETION_COMPLETE_FAIL:
                    $completionicon = 'auto-fail'; break;
            }
        }
        if ($completionicon) {
            $formattedname = $mod->get_formatted_name();
            $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);
            if ($completion == COMPLETION_TRACKING_MANUAL && !$this->page->user_is_editing()) {
                $imgtitle = get_string('completion-title-' . $completionicon, 'completion', $formattedname);
                $newstate =
                    $completiondata->completionstate == COMPLETION_COMPLETE
                    ? COMPLETION_INCOMPLETE
                    : COMPLETION_COMPLETE;
                // In manual mode the icon is a toggle form...

                // If this completion state is used by the
                // conditional activities system, we need to turn
                // off the JS.
                $extraclass = '';
                if (!empty($CFG->enableavailability) &&
                        condition_info::completion_value_used_as_condition($course, $mod)) {
                    $extraclass = ' preventjs';
                }
                $output .= html_writer::start_tag('form', array('method' => 'post',
                    'action' => new moodle_url('/course/togglecompletion.php'),
                    'class' => 'togglecompletion'. $extraclass));
                $output .= html_writer::start_tag('div');
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'modulename', 'value' => $mod->name));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'image',
                    'src' => $this->output->pix_url('i/completion-'.$completionicon),
                    'alt' => $imgalt, 'title' => $imgtitle));
                $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('form');
            } else {
                // In auto mode, or when editing, the icon is just an image
                $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                        array('title' => $imgalt));
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                        array('class' => 'autocompletion'));
            }
        }
        return $output;
    }

    /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * This function is internal and is only used to create CSS classes for the module name/text
     *
     * @param cm_info $mod
     * @return bool
     */
    protected function is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $conditionalhidden = $mod->availablefrom > time() ||
                ($mod->availableuntil && $mod->availableuntil < time()) ||
                count($mod->conditionsgrade) > 0 ||
                count($mod->conditionscompletion) > 0;
        }
        return $conditionalhidden;
    }

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
        global $CFG;
        $output = '';
        if (!$mod->uservisible &&
                (empty($mod->showavailability) || empty($mod->availableinfo))) {
            // nothing to be displayed to the user
            return $output;
        }
        $url = $mod->get_url();
        if (!$url) {
            return $output;
        }

        //Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = $mod->get_formatted_name();
        $altname = '';
        $altname = $mod->modfullname;
        // Avoid unnecessary duplication: if e.g. a forum name already
        // includes the word forum (or Forum, etc) then it is unhelpful
        // to include that in the accessible description that is added.
        if (false !== strpos(textlib::strtolower($instancename),
                textlib::strtolower($altname))) {
            $altname = '';
        }
        // File type after name, for alphabetic lists (screen reader).
        if ($altname) {
            $altname = get_accesshide(' '.$altname);
        }

        $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
        $accessiblebutdim = !$mod->visible || $conditionalhidden;

        $linkclasses = '';
        $accesstext = '';
        $textclasses = '';
        if ($accessiblebutdim) {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed_text';
            if ($conditionalhidden) {
                $linkclasses .= ' conditionalhidden';
                $textclasses .= ' conditionalhidden';
            }
            if ($mod->uservisible) {
                // show accessibility note only if user can access the module himself
                $accesstext = get_accesshide(get_string('hiddenfromstudents').': ');
            }
        }

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $onclick = htmlspecialchars_decode($mod->get_on_click(), ENT_QUOTES);

        $groupinglabel = '';
        if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', context_course::instance($mod->course))) {
            $groupings = groups_get_all_groupings($mod->course);
            $groupinglabel = html_writer::tag('span', '('.format_string($groupings[$mod->groupingid]->name).')',
                    array('class' => 'groupinglabel '.$textclasses));
        }

        // Display link itself.
        $activitylink = html_writer::empty_tag('img', array('src' => $mod->get_icon_url(),
                'class' => 'iconlarge activityicon', 'alt' => $mod->modfullname)) . $accesstext .
                html_writer::tag('span', $instancename . $altname, array('class' => 'instancename'));
        if ($mod->uservisible) {
            $output .= html_writer::link($url, $activitylink, array('class' => $linkclasses, 'onclick' => $onclick)) .
                    $groupinglabel;
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->uservisible)
            $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses)) .
                    $groupinglabel;
        }
        return $output;
    }

    /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_text(cm_info $mod, $displayoptions = array()) {
        $output = '';
        if (!$mod->uservisible &&
                (empty($mod->showavailability) || empty($mod->availableinfo))) {
            // nothing to be displayed to the user
            return $output;
        }
        $content = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));
        $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
        $accessiblebutdim = !$mod->visible || $conditionalhidden;
        $textclasses = '';
        $accesstext = '';
        if ($accessiblebutdim) {
            $textclasses .= ' dimmed_text';
            if ($conditionalhidden) {
                $textclasses .= ' conditionalhidden';
            }
            if ($mod->uservisible) {
                // show accessibility note only if user can access the module himself
                $accesstext = get_accesshide(get_string('hiddenfromstudents').': ');
            }
        }
        if ($mod->get_url()) {
            if ($content) {
                // If specified, display extra content after link.
                $output = html_writer::tag('div', $content, array('class' =>
                        trim('contentafterlink ' . $textclasses)));
            }
        } else {
            // No link, so display only content.
            $output = html_writer::tag('div', $accesstext . $content, array('class' => $textclasses));
        }
        return $output;
    }

    /**
     * Renders HTML to show course module availability information (for someone who isn't allowed
     * to see the activity itself, or for staff)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_availability(cm_info $mod, $displayoptions = array()) {
        global $CFG;
        if (!$mod->uservisible) {
            // this is a student who is not allowed to see the module but might be allowed
            // to see availability info (i.e. "Available from ...")
            if (!empty($mod->showavailability) && !empty($mod->availableinfo)) {
                $output = html_writer::tag('div', $mod->availableinfo, array('class' => 'availabilityinfo'));
            }
            return $output;
        }
        // this is a teacher who is allowed to see module but still should see the
        // information that module is not available to all/some students
        $modcontext = context_module::instance($mod->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $modcontext);
        if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Don't add availability information if user is not editing and activity is hidden.
            if ($mod->visible || $this->page->user_is_editing()) {
                $hidinfoclass = '';
                if (!$mod->visible) {
                    $hidinfoclass = 'hide';
                }
                $ci = new condition_info($mod);
                $fullinfo = $ci->get_full_information();
                if($fullinfo) {
                    return '<div class="availabilityinfo '.$hidinfoclass.'">'.get_string($mod->showavailability
                        ? 'userrestriction_visible'
                        : 'userrestriction_hidden','condition',
                        $fullinfo).'</div>';
                }
            }
        }
        return '';
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link cm_info::get_after_link()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2a) The 'showavailability' option is not set (if that is set,
        //     we need to display the activity so we can show
        //     availability info)
        // or
        // 2b) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible &&
            (empty($mod->showavailability) || empty($mod->availableinfo))) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }
        $output .= html_writer::start_tag('div', array('class' => $indentclasses));

        // Start the div for the activity title, excluding the edit icons.
        $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));

        // Display the link to the module (or do nothing if module has no url)
        $output .= $this->course_section_cm_name($mod, $displayoptions);

        // Module can put text after the link (e.g. forum unread)
        $output .= $mod->get_after_link();

        // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
        $output .= html_writer::end_tag('div'); // .activityinstance

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->get_url();
        if (empty($url)) {
            $output .= $contentpart;
        }

        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $output .= ' '. $this->course_section_cm_edit_actions($editactions);
            $output .= $mod->get_after_edit_icons();
        }

        $output .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // show availability info (if module is not available)
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // $indentclasses
        return $output;
    }

    /**
     * Renders HTML to display a list of course modules in a course section
     * Also displays "move here" controls in Javascript-disabled mode
     *
     * This function calls {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course course object
     * @param int|stdClass|section_info $section relative section number or section object
     * @param int $sectionreturn section number to return to
     * @param int $displayoptions
     * @return void
     */
    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->course_section_cm($course,
                        $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        if (!empty($moduleshtml) || $ismoving) {

            $output .= html_writer::start_tag('ul', array('class' => 'section img-text'));

            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $output .= html_writer::tag('li', html_writer::link($movingurl, $this->output->render($movingpix)),
                            array('class' => 'movehere', 'title' => $strmovefull));
                }

                $mod = $modinfo->cms[$modnumber];
                $modclasses = 'activity '. $mod->modname. ' modtype_'.$mod->modname. ' '. $mod->get_extra_classes();
                $output .= html_writer::start_tag('li', array('class' => $modclasses, 'id' => 'module-'. $mod->id));
                $output .= $modulehtml;
                $output .= html_writer::end_tag('li');
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $output .= html_writer::tag('li', html_writer::link($movingurl, $this->output->render($movingpix)),
                        array('class' => 'movehere', 'title' => $strmovefull));
            }

            $output .= html_writer::end_tag('ul'); // .section
        }

        return $output;
    }

    /**
     * Renders course category contents
     *
     * This function is recursive. Atrribute depth in $coursecatr object reflects
     * the level of recursion. On the 0-level the list is wrapped in div.course_category_tree
     * and JS is included
     *
     * @param coursecat_renderable $coursecatr
     * @return string
     */
    protected function render_coursecat_renderable(coursecat_renderable $coursecatr) {
        $depth = $coursecatr->get_attr(coursecat_renderable::DEPTH);
        $content = '';
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }
        $hassubcategories = !$coursecatr->get_attr(coursecat_renderable::OMITSUBCATEGORIES) &&
                ($coursecatr->get_child_categories_count() > 0);
        $hascourses = ($coursecatr->get_attr(coursecat_renderable::DISPLAYCOURSES) !== 'none') &&
                ($coursecatr->get_child_courses_count() > 0);

        if ($depth == 0) {
            // Generate an id and the required JS call to make this a nice widget
            $id = html_writer::random_id('course_category_tree');
            $this->page->requires->js_init_call('M.util.init_toggle_class_on_click',
                    array($id, '.category.with_children .category_label', 'collapsed', '.category.with_children'));

            // Start content generation
            $content .= html_writer::start_tag('div', array('class' => 'course_category_tree', 'id' => $id));
        }

        $classes = array('category');
        if (empty($coursecatr->get_category()->visible)) {
            $classes[] = 'dimmed_category';
        }
        if ($hassubcategories || $hascourses) {
            $classes[] = 'with_children';
            $expanddepth = $coursecatr->get_attr(coursecat_renderable::EXPANDSUBCATEGORIESDEPTH);
            if ($depth && $expanddepth > 0 && $depth >= $expanddepth) {
                $classes[] = 'collapsed';
                // TODO not only mark collapsed but also do not display subcategories and courses,
                // they will be loaded in AJAX request or displayed on separate page for non-js users
            }
        }
        $content .= html_writer::start_tag('div', array('class' => join(' ', $classes)));
        if ($coursecatr->id) {
            $categoryname = $coursecatr->get_formatted_name();
            $content .= html_writer::start_tag('div', array('class' => 'category_label'));
            $content .= html_writer::link(new moodle_url('/course/category.php',
                    array('id' => $coursecatr->id)),
                    $categoryname, array('class' => 'category_link'));
            $content .= html_writer::end_tag('div');
        }

        // Subcategories
        if ($hassubcategories) {
            $content .= html_writer::start_tag('div', array('class' => 'subcategories'));
            foreach ($coursecatr->get_child_categories() as $subcategory) {
                $content .= $this->render($subcategory);
            }
            $content .= html_writer::end_tag('div');
        }
        // Courses
        if ($hascourses) {
            $content .= html_writer::start_tag('div', array('class' => 'courses'));
            $coursecount = 0;
            foreach ($coursecatr->get_child_courses() as $course) {
                $classes = array('course');
                $linkclass = 'course_link';
                if (!$course->visible) {
                    $linkclass .= ' dimmed';
                }
                $coursecount ++;
                $classes[] = ($coursecount%2) ? 'odd' : 'even';
                $content .= html_writer::start_tag('div', array('class' => join(' ', $classes))); // .course
                $coursename = $coursecatr->get_course_formatted_name($course);
                $content .= html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                        $coursename, array('class' => $linkclass));
                $content .= html_writer::start_tag('div', array('class'=>'course_info clearfix')); // .course_info

                // print enrol info
                if ($icons = enrol_get_course_info_icons($course)) {
                    foreach ($icons as $pix_icon) {
                        $content .= $this->render($pix_icon);
                    }
                }

                if ($course->has_summary() || $course->has_course_contacts()) {
                    if ($coursecatr->get_attr(coursecat_renderable::DISPLAYCOURSES) !== 'expanded') {
                        $url = new moodle_url('/course/info.php', array('id' => $course->id));
                        $image = html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/info'),
                            'alt' => $this->strings->summary));
                        $content .= $this->action_link($url, $image, new popup_action('click', $url, 'courseinfo'),
                                array('title' => $this->strings->summary));
                    }
                }
                $content .= html_writer::end_tag('div'); // .course_info

                if ($course->has_summary() || $course->has_course_contacts()) {
                    if ($coursecatr->get_attr(coursecat_renderable::DISPLAYCOURSES) === 'expanded') {
                        if ($course->has_summary()) {
                            $content .= html_writer::start_tag('div', array('class' => 'course_description'));
                            $content .= $coursecatr->get_course_formatted_summary($course,
                                    array('overflowdiv' => true, 'noclean' => true, 'para' => false));
                            $content .= html_writer::end_tag('div'); // .course_description
                        }
                        if ($course->has_course_contacts()) {
                            $content .= html_writer::start_tag('ul', array('class' => 'teachers'));
                            foreach ($course->get_course_contacts() as $userid => $coursecontact) {
                                $name = $coursecontact['rolename'].': '.
                                        html_writer::link(new moodle_url('/user/view.php',
                                                array('id' => $userid, 'course' => SITEID)),
                                            $coursecontact['username']);
                                $content .= html_writer::tag('li', $name);
                            }
                            $content .= html_writer::end_tag('ul'); // .teachers
                        }
                    }
                }
                $content .= html_writer::end_tag('div'); // .course
            }
            $content .= html_writer::end_tag('div'); // .courses
        }

        $content .= html_writer::end_tag('div'); // .category

        if ($depth == 0) {
            if ($hassubcategories) {
                $content .= html_writer::start_tag('div', array('class'=>'controls'));
                $content .= html_writer::tag('div', get_string('collapseall'), array('class'=>'addtoall expandall'));
                $content .= html_writer::tag('div', get_string('expandall'), array('class'=>'removefromall collapseall'));
                $content .= html_writer::end_tag('div');
            }

            $content .= html_writer::end_tag('div'); // .course-category-tree
        }

        // Return the course category tree HTML
        return $content;
    }

    /** invoked from /index.php */
    public function courses_list_frontpage($displaytype) {
        global $CFG, $DB;
        $content = '';
        $hassiteconfig = has_capability('moodle/site:config', context_system::instance());
        if ($displaytype == FRONTPAGECATEGORYNAMES) {
            $content .= html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(get_string('categories'))), array('href'=>'#skipcategories', 'class'=>'skip-block'));

            //wrap frontpage category names in div container
            $content .= html_writer::start_tag('div', array('id'=>'frontpage-category-names'));

            $content .= $this->heading(get_string('categories'), 2, 'headingblock header');
            $coursecategory = new coursecat_renderable(0,
                    array(
                    coursecat_renderable::EXPANDSUBCATEGORIESDEPTH => $CFG->maxcategorydepth,
                    coursecat_renderable::DISPLAYCOURSES => 'none',
            ));
            $content .= $this->render($coursecategory);
            $content .= $this->course_search_form('', 'short');

            //end frontpage category names div container
            $content .= html_writer::end_tag('div');

            $content .= html_writer::tag('span', '', array('class'=>'skip-block-to', 'id'=>'skipcategories'));
        }

        if ($displaytype == FRONTPAGECATEGORYCOMBO) {
            $content .= html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(get_string('courses'))), array('href'=>'#skipcourses', 'class'=>'skip-block'));

            //wrap frontpage category combo in div container
            $content .= html_writer::start_tag('div', array('id'=>'frontpage-category-combo'));

            $content .= $this->heading(get_string('courses'), 2, 'headingblock header');
            // if there are too many courses, building course category tree could be slow,
            // users should go to course index page to see the whole list.
            $coursecount = $DB->count_records('course');
            if (empty($CFG->numcoursesincombo)) {
                // if $CFG->numcoursesincombo hasn't been set, use default value 500
                $CFG->numcoursesincombo = 500;
            }
            if ($coursecount > $CFG->numcoursesincombo) {
                $link = new moodle_url('/course/');
                $content .= $this->notification(get_string('maxnumcoursesincombo', 'moodle', array('link'=>$link->out(), 'maxnumofcourses'=>$CFG->numcoursesincombo, 'numberofcourses'=>$coursecount)));
            } else {
                $coursecategory = new coursecat_renderable(0,
                        array(
                        coursecat_renderable::EXPANDSUBCATEGORIESDEPTH => $CFG->maxcategorydepth,
                        coursecat_renderable::DISPLAYCOURSES => 'collapsed',
                ));
                $content .= $this->render($coursecategory);
            }
            $content .= $this->course_search_form('', 'short');

            //end frontpage category combo div container
            $content .= html_writer::end_tag('div');

            $content .= html_writer::tag('span', '', array('class'=>'skip-block-to', 'id'=>'skipcourses'));
        }

        if ($displaytype == FRONTPAGECOURSELIST) {
            $ncourses = $DB->count_records('course');
            if (isloggedin() and !$hassiteconfig and !isguestuser() and empty($CFG->disablemycourses)) {
                $content .= html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(get_string('mycourses'))), array('href'=>'#skipmycourses', 'class'=>'skip-block'));

                //wrap frontpage course list in div container
                $content .= html_writer::start_tag('div', array('id'=>'frontpage-course-list'));

                $content .= $this->heading(get_string('mycourses'). 'aaaaaaaaa', 2, 'headingblock header');
                $coursecategory = new coursecat_renderable(0,
                    array(
                        coursecat_renderable::OMITSUBCATEGORIES => true,
                        coursecat_renderable::ENROLLEDCOURSESONLY => true,
                        coursecat_renderable::DISPLAYCOURSES => 'expanded',
                ));
                $content .= $this->render($coursecategory);

                //end frontpage course list div container
                $content .= html_writer::end_tag('div');

                $content .= html_writer::tag('span', '', array('class'=>'skip-block-to', 'id'=>'skipmycourses'));
            } else if ((!$hassiteconfig and !isguestuser()) or ($ncourses <= FRONTPAGECOURSELIMIT)) {
                // admin should not see list of courses when there are too many of them
                $content .= html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(get_string('availablecourses'))), array('href'=>'#skipavailablecourses', 'class'=>'skip-block'));

                //wrap frontpage course list in div container
                $content .= html_writer::start_tag('div', array('id'=>'frontpage-course-list'));

                $content .= $this->heading(get_string('availablecourses'), 2, 'headingblock header');
                $coursecategory = new coursecat_renderable(0,
                    array(
                        coursecat_renderable::OMITSUBCATEGORIES => true,
                        coursecat_renderable::DISPLAYCOURSES => 'expanded',
                ));
                $content .= $this->render($coursecategory);

                //end frontpage course list div container
                $content .= html_writer::end_tag('div');

                $content .= html_writer::tag('span', '', array('class'=>'skip-block-to', 'id'=>'skipavailablecourses'));
            } else {
                $content .= html_writer::tag('div', get_string('therearecourses', '', $ncourses), array('class' => 'notifyproblem'));
                $content .= $this->course_search_form('', 'short');
            }
        }
        return $content;
    }

    /** invoked from /course/index.php */
    public function coursecat($category) {
        global $CFG, $DB;
        require_once($CFG->libdir. '/coursecatlib.php');
        $options = array(
            coursecat_renderable::DISPLAYCOURSES => 'collapsed',
            //coursecat_renderable::SORTCOURSES => 'sortorder',
            //coursecat_renderable::SORTCATEGORIES => 'sortorder',
            coursecat_renderable::EXPANDSUBCATEGORIESDEPTH => 1,
        );
        $coursecategory = new coursecat_renderable(
                is_object($category) ? $category->id : $category,
                $options);

        $site = get_site();
        $output = '';
        if ($coursecategory->id) {
            $this->page->set_title("$site->shortname: ". $coursecategory->get_formatted_name());
            // Print the category selector
            $output .= html_writer::start_tag('div', array('class' => 'categorypicker'));
            $select = new single_select(new moodle_url('/course/category.php'), 'id',
                    coursecat::make_categories_list(), $coursecategory->id, null, 'switchcategory');
            $select->set_label(get_string('categories').':');
            $output .= $this->render($select);
            $output .= html_writer::end_tag('div'); // .categorypicker
            // Print current category description
            $output .= $this->box($coursecategory->get_formatted_description());
        } else {
            if (coursecat::count_all() == 1 && $DB->count_records('course') <= 200) {
                // simple display, without categories
                $strfulllistofcourses = get_string('fulllistofcourses');
                $this->page->set_title("$site->shortname: $strfulllistofcourses");
                $coursecategory->set_attr(coursecat_renderable::OMITSUBCATEGORIES, true);
            } else {
                $strcategories = get_string('categories');
                $this->page->set_title("$site->shortname: $strcategories");
            }
        }

        $output .= $this->render($coursecategory);

        // add course search form (to the navbar in case of category)
        if (!$coursecategory->id) {
            $output .= $this->course_search_form();
        } else {
            $this->page->set_button($this->course_search_form('', 'navbar'));
        }

        $output .= $this->container_start('buttons');
        $context = get_category_or_system_context($coursecategory->id);
        if (has_capability('moodle/course:create', $context)) {
            // Print link to create a new course, for the 1st available category.
            if ($coursecategory->id) {
                $url = new moodle_url('/course/edit.php', array('category' => $coursecategory->id, 'returnto' => 'category'));
            } else {
                $url = new moodle_url('/course/edit.php', array('category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat'));
            }
            $output .= $this->single_button($url, get_string('addnewcourse'), 'get');
        }
        ob_start();
        print_course_request_buttons($context);
        $output .= ob_get_contents();
        ob_end_clean();
        $output .= $this->container_end();

        return $output;
    }

}

/**
 * Class storing display options and functions to help display course category and/or courses lists
 *
 * This is a wrapper for coursecat objects that also stores display options
 * and functions to retrieve sorted and paginated lists of categories/courses.
 *
 * If theme overrides methods in core_course_renderers that access this class
 * it may as well not use this class at all or extend it.
 *
 * @package   core
 * @copyright 2013 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursecat_renderable implements renderable {
    protected $id = 0;
    protected $attributes = array();

    /** @var string attribute : [none, collapsed, expanded] how (if) display courses list */
    const DISPLAYCOURSES = 'collapsed'; // TODO: [countonly, auto]
    /** @var string attribute : depth to expand subcategories in the tree (deeper subcategories will be loaded by AJAX or proceed to category page by clicking on category name) */
    const EXPANDSUBCATEGORIESDEPTH = 'expanddepth'; // TODO rename to subcategorydepth for better understanding
    /** @var string attribute : for small sites, do not display categories names just list all courses in all subcategories */
    const OMITSUBCATEGORIES = 'omitcat';
    /** @var string attribute : return courses where user is enrolled only */
    const ENROLLEDCOURSESONLY = 'enrolled';
    /** @var string attribute : how to sort courses */
    //const SORTCOURSES = 'sort';
    /** @var string attribute : how to sort subcategories */
    //const SORTCATEGORIES = 'sortcat';
    /** @var string attribute : limit the number of subcategories inside one category.
     * If there are more categories, a link "More categories..." is displayed,
     * which leads to the subcategory page, or displays the next page or loads
     * more entries via AJAX. Defaults to $CFG->coursesperpage.
     * Also can be concatenated with level: course_category::sortcategories.'2' */
    //const CATEGORIESLIMIT = 'limitcat';
    /** @var string attribute : limit the number of courses inside one category.
     * If there are more courses, a link "More courses..." is displayed which
     * leads to the subcategory page, or displays the next page or loads more
     * entries via AJAX. Defaults to $CFG->coursesperpage */
    //const COURSESLIMIT = 'limit';
    /** @var string attribute : completely disable AJAX loading even if browser
     * supports it */
    //const AJAXDISABLED = 'noajax';
    /** @var string attribute : add a heading (?) */
    //const HEADING = 'heading';
    /** @var string attribute : depth of this category in the current view */
    const DEPTH = 'depth';
    /** @var string attribute : search string in courses names and/or descriptions */
    //const SEARCHSTRING = 'search';
    /** @var string attribute : display category name in course description
     * (may be used in search results or in 'my courses' lists) */
    //const DISPLAYCATEGORYNAME = 'showcatname';

    /**
     * Constructor
     *
     * @param array $attributes array of category retrive/display attributes
     *     where keys are the constants defined above
     */
    public function __construct($id = 0, $attributes = array()) {
//        global $CFG;
        $this->id = $id;
        if (empty($attributes)) {
            $attributes = array();
        }
        if (!is_array($attributes)) {
            $attributes = (array)$attributes;
        }
        // defaults:
        $defaults = array(
            self::DEPTH => 0,
//            self::CATEGORIESLIMIT => $CFG->coursesperpage,
//            self::COURSESLIMIT => $CFG->coursesperpage,
//            self::EXPANDSUBCATEGORIESDEPTH => 5,// TODO $CFG->maxcategorydepth
        );
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $attributes)) {
                $attributes[$key] = $value;
            }
        }
        $this->attributes = $attributes;
    }

    /**
     * Magic method to get category id property
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'id') {
            return $this->id;
        }
        return null;
    }

    /**
     * Get the category attribute. Some attributes are substituted with
     * defaults or overwritten
     *
     * @param string $name
     * @return mixed
     */
    public function get_attr($name) {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return null;
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     */
    public function set_attr($name, $value) {
        $this->attributes[$name] = $value;
    }

    /**
     * Returns a coursecat object representing DB row in course_categories
     * If $this->id is not specified, a pseudo 0-category is returned
     *
     * @param $strictness whether to throw an exception if category does not exist
     * @return coursecat
     */
    public function get_category($strictness = MUST_EXIST) {
        global $CFG;
        require_once($CFG->libdir. '/coursecatlib.php');
        return coursecat::get($this->id, $strictness);
    }

    /**
     * Returns formatted and filtered name of the current category
     *
     * @param array $options format options, if context is not specified
     *     it will be added automatically
     * @return string|null name or null for the 0-category
     */
    public function get_formatted_name($options = array()) {
        return $this->get_category()->get_formatted_name($options);
    }

    /**
     * Returns formatted and filtered description of current category
     *
     * @param array $options format options, by default [noclean,overflowdiv],
     *     if context is not specified it will be added automatically
     * @return string|null
     */
    public function get_formatted_description($options = null) {
        $cat = $this->get_category();
        if ($cat->id && !empty($cat->description)) {
            if (!isset($cat->descriptionformat)) {
                $descriptionformat = FORMAT_MOODLE;
            } else {
                $descriptionformat = $cat->descriptionformat;
            }
            if ($options === null) {
                $options = array('noclean' => true, 'overflowdiv' => true);
            }
            if (!isset($options['context'])) {
                $options['context'] = context_coursecat::instance($cat->id);
            }
            $text = file_rewrite_pluginfile_urls($cat->description,
                    'pluginfile.php', $options['context']->id, 'coursecat', 'description', null);
            return format_text($text, $descriptionformat, $options);
        }
        return null;
    }

    /**
     * Gets the child categories of a given courses category
     *
     * The objects in the return array have proper set attributes
     * DEPTH, EXPANDSUBCATEGORIESDEPTH
     *
     * @return array of course_category instances
     */
    function get_child_categories() {
        $childcategories = array();
        if ($this->get_attr(self::OMITSUBCATEGORIES)) {
            return $childcategories;
        }
        if ($cat = $this->get_category(IGNORE_MISSING)) {
            foreach ($cat->get_children() as $child) {
                $attr = $this->attributes + array();
                $attr[self::DEPTH] = $this->get_attr(self::DEPTH) + 1;
                $childcategories[$child->id] = new coursecat_renderable($child->id, $attr);
            }
        }
        return $childcategories;
    }

    /**
     * Returns the count of the child categories
     *
     * @return int
     */
    function get_child_categories_count() {
        if ($cat = $this->get_category(IGNORE_MISSING)) {
            $children = $cat->get_children();
            return count($children);
        }
        return 0;
    }

    /**
     * Returns array of courses in this category
     *
     * @return array of rows from DB {courses} table
     */
    public function get_child_courses() {
        if ($this->get_attr(self::DISPLAYCOURSES) == 'none') {
            return array();
        }
        $fullinfo = $this->get_attr(self::DISPLAYCOURSES) == 'expanded';
        $childcourses = $this->get_category()->get_courses(
                array('recursive' => $this->get_attr(self::OMITSUBCATEGORIES),
                    'enrolledonly' => $this->get_attr(self::ENROLLEDCOURSESONLY),
                    'summary' => $fullinfo,
                    'coursecontacts' => $fullinfo));
        return $childcourses;
    }

    /**
     * Returns the number of visible courses in this category
     *
     * @return int
     */
    public function get_child_courses_count() {
        $childcourses = $this->get_category()->get_courses(
                array('recursive' => $this->get_attr(self::OMITSUBCATEGORIES)));
        return count($childcourses);
    }

    /**
     * Returns summary formatted to course context
     *
     * @param course_in_list $course
     * @param array|stdClass $options additional formatting options
     * @return string
     */
    public function get_course_formatted_summary($course, $options = array()) {
        if (!$course->has_summary()) {
            return '';
        }
        $options = (array)$options;
        $context = $course->get_context();
        if (!isset($options['context'])) {
            $options['context'] = context_course::instance($course->id);
        }
        $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
        return format_text($summary, $course->summaryformat, $options, $course->id);
    }

    public function get_course_formatted_name($course, $options = array()) {
        global $CFG;
        if (!empty($CFG->courselistshortnames)) {
            // convert course to stdClass to be able to pass to get_string
            $obj = (object)convert_to_array($course);
            $name = get_string('courseextendednamedisplay', '', $obj);
        } else {
            $name = $course->fullname;
        }
        $options = (array)$options;
        if (!isset($options['context'])) {
            $options['context'] = context_course::instance($course->id);
        }
        return format_string($name, true, $options);
    }
}
