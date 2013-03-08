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
     * Displays one course in the list of courses.
     *
     * This is a help function for core_coursecat_renderer::render_coursecat_renderable()
     *
     * @param course_in_list $course
     * @param coursecat_renderable $coursecatr contains display attributes
     * @param array $additionalclasses additional classes to add to the main <div> tag (usually
     *    depend on the course position in list - first/last/even/odd)
     * @return string
     */
    protected function coursecat_course_link($course, coursecat_renderable $coursecatr, $additionalclasses = array()) {
        if ($coursecatr->get_show_courses() == coursecat_renderable::SHOW_COURSES_NONE) {
            return '';
        }
        $content = '';
        $classes = array('course') + $additionalclasses;
        $linkclass = 'course_link';
        if (!$course->visible) {
            $linkclass .= ' dimmed';
        }
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

        // If we display course in collapsed form but the course has summary or course contacts, display the link to the info page.
        if ($course->has_summary() || $course->has_course_contacts()) {
            if ($coursecatr->get_show_courses() < coursecat_renderable::SHOW_COURSES_EXPANDED) {
                $url = new moodle_url('/course/info.php', array('id' => $course->id));
                $image = html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/info'),
                    'alt' => $this->strings->summary));
                $content .= $this->action_link($url, $image, new popup_action('click', $url, 'courseinfo'),
                        array('title' => $this->strings->summary));
            }
        }

        $content .= html_writer::end_tag('div'); // .course_info

        // If course is displayed in expanded form - show summary, category and contacts
        if ($coursecatr->get_show_courses() >= coursecat_renderable::SHOW_COURSES_EXPANDED) {
            // display course summary
            if ($course->has_summary()) {
                $content .= html_writer::start_tag('div', array('class' => 'course_description'));
                $content .= $coursecatr->get_course_formatted_summary($course,
                        array('overflowdiv' => true, 'noclean' => true, 'para' => false));
                $content .= html_writer::end_tag('div'); // .course_description
            }
            // display course category if necessary (for example in search results)
            if ($coursecatr->get_show_courses() == coursecat_renderable::SHOW_COURSES_EXPANDED_WITH_CAT
                    && ($cat = coursecat::get($course->category, IGNORE_MISSING))) {
                $content .= html_writer::start_tag('div', array('class' => 'course_category'));
                $content .= get_string('category').': '.
                        html_writer::link(new moodle_url('/course/category.php', array('id' => $cat->id)),
                                $cat->get_formatted_name(), array('class' => $cat->visible ? '' : 'dimmed'));
                $content .= html_writer::end_tag('div'); // .course_description
            }
            // display course contacts. See course_in_list::get_course_contacts()
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
        $content .= html_writer::end_tag('div'); // .course
        return $content;
    }

    /**
     * Renders the list of courses
     *
     * This is a help function for core_coursecat_renderer::render_coursecat_renderable()
     *
     * @param coursecat_renderable $coursecatr
     * @return string
     */
    protected function coursecat_courses(coursecat_renderable $coursecatr) {
        global $CFG;
        if ($coursecatr->get_show_courses() == coursecat_renderable::SHOW_COURSES_NONE) {
            // courses are not displayed in this view at all
            return '';
        }
        if ($coursecatr->get_subcat_depth() > 0 && $coursecatr->get_depth() >= $coursecatr->get_subcat_depth()) {
            // we don't load any content in this category
            return '';
        }
        $courses = array();
        if (!$coursecatr->get_courses_display_option('nodisplay')) {
            $courses =  $coursecatr->get_child_courses();
        }
        $totalcount = $coursecatr->get_child_courses_count();
        if (!$totalcount) {
            // Note that we call get_child_courses_count() AFTER get_child_courses() to avoid extra DB requests.
            // Courses count is cached during courses retrieval.
            return '';
        }

        // prepare content of paging bar if it is needed
        $paginationurl = $coursecatr->get_courses_display_option('paginationurl');
        $paginationallowall = $coursecatr->get_courses_display_option('paginationallowall');
        if ($totalcount > count($courses)) {
            // there are more results that can fit on one page
            if ($paginationurl) {
                // the option paginationurl was specified, display pagingbar
                $perpage = $coursecatr->get_courses_display_option('limit', $CFG->coursesperpage);
                $page = $coursecatr->get_courses_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                        $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                            get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $coursecatr->get_courses_display_option('viewmoreurl')) {
                // the option for 'View more' link was specified, display more link (if it is link to category view page, add category id)
                if ($viewmoreurl->compare(new moodle_url('/course/category.php'), URL_MATCH_BASE)) {
                    if ($coursecatr->id) {
                        $viewmoreurl->param('id', $coursecatr->id);
                    } else {
                        $viewmoreurl = new moodle_url('/course/index.php', $viewmoreurl->params());
                    }
                }
                $viewmoretext = $coursecatr->get_courses_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
        }

        // display list of courses
        $content = '';
        $content .= html_writer::start_tag('div', array('class' => 'courses'));

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }

        $coursecount = 0;
        foreach ($courses as $course) {
            $coursecount ++;
            $classes = array(($coursecount%2) ? 'odd' : 'even');
            if ($coursecount == 1) {
                $classes[] = 'first';
            }
            if ($coursecount > count($courses)) {
                $classes[] = 'last';
            }
            $content .= $this->coursecat_course_link($course, $coursecatr, $classes);
        }

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }
        if (!empty($morelink)) {
            $content .= $morelink;
        }

        $content .= html_writer::end_tag('div'); // .courses
        return $content;
    }

    /**
     * Renders the list of subcategories in a category
     *
     * This is a help function for core_coursecat_renderer::render_coursecat_renderable()
     *
     * @param coursecat_renderable $coursecatr
     * @return string
     */
    protected function coursecat_subcategories(coursecat_renderable $coursecatr) {
        global $CFG;
        if ($coursecatr->get_omit_subcat()) {
            return '';
        }
        if ($coursecatr->get_subcat_depth() > 0 && $coursecatr->get_depth() >= $coursecatr->get_subcat_depth()) {
            // we don't load any content in this category
            return '';
        }
        $subcategories = array();
        if (!$coursecatr->get_categories_display_option('nodisplay')) {
            $subcategories = $coursecatr->get_child_categories();
        }
        $totalcount = $coursecatr->get_child_categories_count();
        if (!$totalcount) {
            // Note that we call get_child_categories_count() AFTER get_child_categories() to avoid extra DB requests.
            // Categories count is cached during children categories retrieval.
            return '';
        }

        // prepare content of paging bar or more link if it is needed
        $paginationurl = $coursecatr->get_categories_display_option('paginationurl');
        $paginationallowall = $coursecatr->get_categories_display_option('paginationallowall');
        if ($totalcount > count($subcategories)) {
            if ($paginationurl) {
                // the option 'paginationurl was specified, display pagingbar
                $perpage = $coursecatr->get_categories_display_option('limit', $CFG->coursesperpage);
                $page = $coursecatr->get_categories_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                        $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                            get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $coursecatr->get_categories_display_option('viewmoreurl')) {
                // the option 'viewmoreurl' was specified, display more link (if it is link to category view page, add category id)
                if ($viewmoreurl->compare(new moodle_url('/course/category.php'), URL_MATCH_BASE)) {
                    if ($coursecatr->id) {
                        $viewmoreurl->param('id', $coursecatr->id);
                    } else {
                        $viewmoreurl = new moodle_url('/course/index.php', $viewmoreurl->params());
                    }
                }
                $viewmoretext = $coursecatr->get_categories_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
        }

        // display list of subcategories
        $content = '';
        $content .= html_writer::start_tag('div', array('class' => 'subcategories'));

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }

        foreach ($subcategories as $subcategory) {
            $content .= $this->render($subcategory);
        }

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }
        if (!empty($morelink)) {
            $content .= $morelink;
        }

        $content .= html_writer::end_tag('div');
        return $content;
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
        $depth = $coursecatr->get_depth();
        $content = '';
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }

        // render html for subcategories and courses beforehead because their presence/absence may affect CSS classes of this category
        $contentsubcategories = $this->coursecat_subcategories($coursecatr);
        $contentcourses = $this->coursecat_courses($coursecatr);

        if ($depth == 0) {
            // Generate an id and the required JS call to make this a nice widget
            $id = html_writer::random_id('course_category_tree');
            $this->page->requires->js_init_call('M.util.init_toggle_class_on_click',
                    array($id, '.category.with_children.loaded > .category_label', 'collapsed', '.category.with_children.loaded'));

            // Start content generation
            $mainclass = $coursecatr->get_display_option('class', '');
            if ($coursecatr->get_omit_subcat()) {
                $mainclass .= ' courses-only';
            }
            $content .= html_writer::start_tag('div', array('id' => $id,
                'class' => 'course_category_tree '. $mainclass));
        }

        $classes = array('category');
        if (empty($coursecatr->get_category()->visible)) {
            $classes[] = 'dimmed_category';
        }
        if ($coursecatr->get_subcat_depth() > 0 && $depth >= $coursecatr->get_subcat_depth()) {
            // do not load content
            $classes[] = 'notloaded';
            if ($coursecatr->get_child_categories_count() ||
                    ($coursecatr->get_show_courses() != coursecat_renderable::SHOW_COURSES_NONE && $coursecatr->get_child_courses_count())) {
                $classes[] = 'with_children';
            }
        } else if ($depth && (!empty($contentsubcategories) || !empty($contentcourses))) {
            $classes[] = 'with_children';
            $classes[] = 'loaded';
        }
        $content .= html_writer::start_tag('div', array('class' => join(' ', $classes)));
        if ($coursecatr->id && $depth) {
            // Note, we do not print category name for the category with depth=0 (top level)
            $categoryname = $coursecatr->get_formatted_name();
            $content .= html_writer::start_tag('div', array('class' => 'category_label'));
            $content .= html_writer::link(new moodle_url('/course/category.php',
                    array('id' => $coursecatr->id)),
                    $categoryname, array('class' => 'category_link'));
            $content .= html_writer::end_tag('div');
        }

        // Subcategories
        $content .= $contentsubcategories;
        // Courses
        $content .= $contentcourses;

        $content .= html_writer::end_tag('div'); // .category

        if ($depth == 0) {
            if (!empty($contentsubcategories) && $coursecatr->get_subcat_depth() != 1) {
                // We don't need to display "Expand all"/"Collapse all" buttons if there are no
                // subcategories or there is only one level of subcategories loaded
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
            $coursecategory = new coursecat_renderable(0);
            $coursecategory->set_subcat_depth($CFG->maxcategorydepth)->
                    set_show_courses(coursecat_renderable::SHOW_COURSES_NONE)->
                    set_categories_display_options(array(
                        'limit' => $CFG->coursesperpage,
                        'viewmoreurl' => new moodle_url('/course/category.php',
                                array('browse' => 'categories', 'page' => 1))
                    ))->
                    set_display_options(array('class' => 'frontpage-category-names'));
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
                $coursecategory = new coursecat_renderable(0);
                $coursecategory->set_subcat_depth($CFG->maxcategorydepth)->
                    set_categories_display_options(array(
                        'limit' => $CFG->coursesperpage,
                        'viewmoreurl' => new moodle_url('/course/category.php',
                                array('browse' => 'categories', 'page' => 1))
                    ))->
                    set_courses_display_options(array(
                        'limit' => $CFG->coursesperpage,
                        'viewmoreurl' => new moodle_url('/course/category.php',
                                array('browse' => 'courses', 'page' => 1))
                    ))->
                    set_display_options(array('class' => 'frontpage-category-combo'));
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
                $coursecategory = new coursecat_renderable(0);
                $coursecategory->set_omit_subcat(true)->set_show_enrolled_only(true)->
                        set_display_options(array('class' => 'frontpage-course-list-enrolled'));
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
                $coursecategory = new coursecat_renderable(0);
                $coursecategory->set_omit_subcat(true)->
                        set_show_courses(coursecat_renderable::SHOW_COURSES_EXPANDED)->
                        set_display_options(array('class' => 'frontpage-course-list-all'));
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

    /**
     * Renders HTML to display particular course category - list of it's subcategories and courses
     *
     * Invoked from /course/index.php
     *
     * @param int|stdClass|coursecat $category
     */
    public function course_category($category) {
        global $CFG, $DB;
        require_once($CFG->libdir. '/coursecatlib.php');
        $coursecategory = new coursecat_renderable(is_object($category) ? $category->id : $category);
        $coursecategory->set_display_options(array('class' => 'category-browse category-browse-'.$coursecategory->id));
        $site = get_site();
        $output = '';

        $coursedisplayoptions = array();
        $catdisplayoptions = array();
        $browse = optional_param('browse', null, PARAM_ALPHA);
        $perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        if ($coursecategory->id) {
            $baseurl = new moodle_url('/course/category.php', array('id' => $coursecategory->id));
        } else {
            $baseurl = new moodle_url('/course/index.php');
        }
        if ($browse === 'courses' || !$coursecategory->get_category()->has_children()) {
            $coursedisplayoptions['limit'] = $perpage;
            $coursedisplayoptions['offset'] = $page * $perpage;
            $coursedisplayoptions['paginationurl'] = new moodle_url($baseurl, array('browse' => 'courses', 'perpage' => $perpage));
            $catdisplayoptions['nodisplay'] = true;
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'categories', 'page' => 0));
            $catdisplayoptions['viewmoretext'] = new lang_string('viewallsubcategores');
        } else if ($browse === 'categories' || !$coursecategory->get_category()->has_courses()) {
            $coursedisplayoptions['nodisplay'] = true;
            $catdisplayoptions['limit'] = $perpage;
            $catdisplayoptions['offset'] = $page * $perpage;
            $catdisplayoptions['paginationurl'] = new moodle_url($baseurl, array('browse' => 'categories', 'perpage' => $perpage));
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'courses', 'page' => 0));
            $coursedisplayoptions['viewmoretext'] = new lang_string('viewallcourses');
        } else {
            // we have a category that has both subcategories and courses, display pagination separately
            $coursedisplayoptions['limit'] = $CFG->coursesperpage;
            $catdisplayoptions['limit'] = $CFG->coursesperpage;
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'courses', 'page' => 1));
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'categories', 'page' => 1));
        }
        $coursecategory->set_courses_display_options($coursedisplayoptions)->set_categories_display_options($catdisplayoptions);

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
                $coursecategory->set_omit_subcat(true);
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

    /**
     * Renders html to display search result page
     *
     * @param array $searchcriteria may contain elements: search, blocklist, modulelist, tagid
     * @return string
     */
    public function search_courses($searchcriteria) {
        global $CFG;
        $content = '';
        if (!empty($searchcriteria)) {
            // print search results

            $displayoptions = array('sort' => array('fullname' => 1));
            // take the current page and number of results per page from query
            $perpage = optional_param('perpage', 0, PARAM_RAW);
            if ($perpage !== 'all') {
                $displayoptions['limit'] = ((int)$perpage <= 0) ? $CFG->coursesperpage : (int)$perpage;
                $page = optional_param('page', 0, PARAM_INT);
                $displayoptions['offset'] = $displayoptions['limit'] * $page;
            }
            // options 'paginationurl' and 'paginationallowall' are only used in method coursecat_courses()
            $displayoptions['paginationurl'] = new moodle_url('/course/search.php', $searchcriteria);
            $displayoptions['paginationallowall'] = true; // allow adding link 'View all'

            $class = 'course-search-result';
            foreach ($searchcriteria as $key => $value) {
                if (!empty($value)) {
                    $class .= ' course-search-result-'. $key;
                }
            }
            $coursecatr = new coursecat_renderable(0);
            $coursecatr->set_omit_subcat(true)->
                    set_show_courses(coursecat_renderable::SHOW_COURSES_EXPANDED_WITH_CAT)->
                    set_search_criteria($searchcriteria)->
                    set_courses_display_options($displayoptions)->
                    set_display_option(array('class' => $class));
            // TODO heading
            $courseslist = $this->render($coursecatr);

            if (!$coursecatr->get_child_courses_count()) {
                if (!empty($searchcriteria['search'])) {
                    $content .= $this->heading(get_string('nocoursesfound', '', $searchcriteria['search']));
                } else {
                    $content .= $this->heading(get_string('novalidcourses'));
                }
            } else {
                $content .= $courseslist;
            }

            if (!empty($searchcriteria['search'])) {
                // print search form only if there was a search by search string, otherwise it is confusing
                $content .= "<br /><br />";
                if (!empty($searchcriteria['search'])) {
                    $content .= $this->course_search_form($searchcriteria['search']);
                } else {
                    $content .= $this->course_search_form();
                }
            }
        } else {
            // just print search form
            $content .= $this->box_start();
            $content .= "<center>";
            $content .= "<br />";
            $content .= $this->course_search_form('', 'plain');
            $content .= html_writer::tag('div', get_string("searchhelp"), array('class' => 'searchhelp'));
            $content .= "</center>";
            $content .= $this->box_end();
        }
        return $content;
    }

    /**
     * Renders html to print list of courses tagged with particular tag
     *
     * @param int $tagid id of the tag
     * @return string empty string if no courses are marked with this tag or rendered list of courses
     */
    public function tagged_courses($tagid) {
        global $CFG;
        $displayoptions = array('limit' => $CFG->coursesperpage);
        $displayoptions['viewmoreurl'] = new moodle_url('/course/search.php',
                array('tagid' => $tagid, 'page' => 1, 'perpage' => $CFG->coursesperpage));
        $displayoptions['viewmoretext'] = new lang_string('findmorecourses');
        $coursecatr = new coursecat_renderable(0);
        $coursecatr->set_omit_subcat(true)->
                set_show_courses(coursecat_renderable::SHOW_COURSES_EXPANDED_WITH_CAT)->
                set_search_criteria(array('tagid' => $tagid))->
                set_courses_display_options($displayoptions)->
                set_display_options(array('class' => 'course-search-result course-search-result-tagid'));
                // (we set the same css class as in search results by tagid)
        $content = $this->render($coursecatr);
        if ($cnt = $coursecatr->get_child_courses_count()) {
            require_once $CFG->dirroot.'/tag/lib.php';
            $heading = get_string('courses') . ' ' . get_string('taggedwith', 'tag', tag_get_name($tagid)) .': '. $cnt;
            return $this->heading($heading, 3). $content;
        }
        return '';
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
    const SHOW_COURSES_NONE = 0;
    const SHOW_COURSES_COLLAPSED = 10;
    const SHOW_COURSES_EXPANDED = 20;
    const SHOW_COURSES_EXPANDED_WITH_CAT = 30;

    /** @var int category id */
    protected $id = 0;
    /** @var coursecat stores related coursecat object */
    protected $coursecat = false;

    /** @var string [none, collapsed, expanded] how (if) display courses list */
    protected $showcourses = 10; /* SHOW_COURSES_COLLAPSED */ // TODO: [countonly, auto]
    /** @var int depth to expand subcategories in the tree (deeper subcategories will be loaded by AJAX or proceed to category page by clicking on category name) */
    protected $subcatdepth = 1;
    /** @var bool for small sites, do not display categories names just list all courses in all subcategories */
    protected $omitsubcat = false;
    /** @var bool return courses where user is enrolled only */
    protected $enrolledonly = false;
    /** @var array options to display courses list */
    protected $coursesdisplayoptions = array();
    /** @var array options to display subcategories list */
    protected $categoriesdisplayoptions = array();
    /** @var array additional options to display course category or course listing */
    protected $displayoptions = array();
    /** @var int depth of this category in the current view */
    protected $depth = 0;
    /** @var array search criteria */
    protected $searchcriteria = null;

    /**
     * Constructor
     *
     * @param int $id
     * @param int $depth depth of the category in the current view
     */
    public function __construct($id = 0, $depth = 0) {
        $this->id = $id;
        $this->depth = $depth;
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
     * Sets the displaycourse display option
     *
     * how (if) display courses list - none, collapsed, expanded, etc.
     *
     * @param int $showcourses SHOW_COURSES_NONE, SHOW_COURSES_COLLAPSED, SHOW_COURSES_EXPANDED, etc.
     * @return coursecat_renderable
     */
    public function set_show_courses($showcourses) {
        $this->showcourses = $showcourses;
        return $this;
    }

    /**
     * Returns the displaycourse display option
     *
     * how (if) display courses list - none, collapsed, expanded, etc.
     *
     * @return int - SHOW_COURSES_NONE, SHOW_COURSES_COLLAPSED, SHOW_COURSES_EXPANDED, etc.
     */
    public function get_show_courses() {
        return $this->showcourses;
    }

    /**
     * Sets the subcatdepth display option
     *
     * depth to expand subcategories in the tree (deeper subcategories will be loaded
     * by AJAX or proceed to category page by clicking on category name)
     *
     * @param int $subcatdepth
     * @return coursecat_renderable
     */
    public function set_subcat_depth($subcatdepth) {
        $this->subcatdepth = $subcatdepth;
        return $this;
    }

    /**
     * Returns the subcatdepth display option
     *
     * depth to expand subcategories in the tree (deeper subcategories will be loaded
     * by AJAX or proceed to category page by clicking on category name)
     *
     * @return int
     */
    public function get_subcat_depth() {
        return $this->subcatdepth;
    }

    /**
     * Sets the omitsubcat display option
     *
     * for small sites, do not display categories names just list all courses in
     * all subcategories. Default false
     *
     * @param bool $omitsubcat
     * @return coursecat_renderable
     */
    public function set_omit_subcat($omitsubcat) {
        $this->omitsubcat = $omitsubcat;
        return $this;
    }

    /**
     * Returns the omitsubcat display option
     *
     * for small sites, do not display categories names just list all courses
     * in all subcategories
     *
     * @return bool
     */
    public function get_omit_subcat() {
        return $this->omitsubcat;
    }

    /**
     * Sets the enrolledonly display option
     *
     * to filter the courses where user is enrolled only
     *
     * @param bool $enrolledonly
     * @return coursecat_renderable
     */
    public function set_show_enrolled_only($enrolledonly) {
        $this->enrolledonly = $enrolledonly;
        return $this;
    }

    /**
     * Returns the enrolledonly display option
     *
     * to filter the courses where user is enrolled only
     *
     * @return bool
     */
    public function get_show_enrolled_only() {
        return $this->enrolledonly;
    }

    /**
     * Sets the search criteria (search string, block id, module name)
     *
     * @param array $searchcriteria
     * @return coursecat_renderable
     */
    public function set_search_criteria($searchcriteria) {
        $this->searchcriteria = $searchcriteria;
        return $this;
    }

    /**
     * Returns the search criteria
     *
     * @return array
     */
    public function get_search_criteria() {
        return $this->searchcriteria;
    }

    /**
     * Sets options to display list of courses
     *
     * Options 'sort', 'offset' and 'limit' are passed to coursecat::get_courses()
     * or coursecat::search_courses(). Any other options may be used by renderer
     * functions
     *
     * @param array $options
     * @return coursecat_renderable
     */
    public function set_courses_display_options($options) {
        $this->coursesdisplayoptions = $options;
        return $this;
    }

    /**
     * Return the specified option to display list of courses
     *
     * @param string $optionname option name, if omitted an array of all options is returned
     * @param mixed $defaultvalue default value for option if it is not specified
     * @return mixed
     */
    public function get_courses_display_option($optionname = null, $defaultvalue = null) {
        if ($optionname === null) {
            return $this->coursesdisplayoptions;
        } else if (array_key_exists($optionname, $this->coursesdisplayoptions)) {
            return $this->coursesdisplayoptions[$optionname];
        } else {
            return $defaultvalue;
        }
    }

    /**
     * Sets options to display list of subcategories
     *
     * Options 'sort', 'offset' and 'limit' are passed to coursecat::get_children().
     * Any other options may be used by renderer functions
     *
     * @param array $options
     * @return coursecat_renderable
     */
    public function set_categories_display_options($options) {
        $this->categoriesdisplayoptions = $options;
        return $this;
    }

    /**
     * Return the specified option to display list of subcategories
     *
     * @param string $optionname option name, if omitted an array of all options is returned
     * @param mixed $defaultvalue default value for option if it is not specified
     * @return mixed
     */
    public function get_categories_display_option($optionname = null, $defaultvalue = null) {
        if ($optionname === null) {
            return $this->categoriesdisplayoptions;
        } else if (array_key_exists($optionname, $this->categoriesdisplayoptions)) {
            return $this->categoriesdisplayoptions[$optionname];
        } else {
            return $defaultvalue;
        }
    }

    /**
     * Sets general display options
     *
     * To pass additional information between renderer methods (i.e. CSS class name)
     *
     * @param array $options
     * @return coursecat_renderable
     */
    public function set_display_options($options) {
        $this->displayoptions = $options;
        return $this;
    }

    /**
     * Return the specified display option
     *
     * @param string $optionname option name, if omitted an array of all options is returned
     * @param mixed $defaultvalue default value for option if it is not specified
     * @return mixed
     */
    public function get_display_option($optionname = null, $defaultvalue = null) {
        if ($optionname === null) {
            return $this->displayoptions;
        } else if (array_key_exists($optionname, $this->displayoptions)) {
            return $this->displayoptions[$optionname];
        } else {
            return $defaultvalue;
        }
    }

    /**
     * Returns the depth property
     *
     * Depth of the category in the current view. There is no setter method
     * because the depth can not be changed after the category is created.
     *
     * @return int
     */
    public function get_depth() {
        return $this->depth;
    }

    /**
     * Returns a coursecat object representing DB row in course_categories
     * If $this->id is not specified, a pseudo 0-category is returned
     *
     * @return coursecat
     */
    public function get_category() {
        global $CFG;
        if ($this->coursecat === false) {
            require_once($CFG->libdir. '/coursecatlib.php');
            $this->coursecat = coursecat::get($this->id, MUST_EXIST);
        }
        return $this->coursecat;
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
     * The objects in the return array inherit this category's display options
     *
     * @return array of course_category instances
     */
    function get_child_categories() {
        $childcategories = array();
        if ($this->get_omit_subcat()) {
            return $childcategories;
        }
        $options = array_intersect_key($this->get_categories_display_option(),
                array('sort' => 1, 'offset' => 1, 'limit' => 1));
        foreach ($this->get_category()->get_children($options) as $child) {
            $childcategories[$child->id] = new coursecat_renderable($child->id, $this->get_depth() + 1);
            $childcategories[$child->id]->set_show_courses($this->get_show_courses()
                    )->set_show_enrolled_only($this->get_show_enrolled_only()
                    )->set_subcat_depth($this->get_subcat_depth()
                    )->set_categories_display_options($this->get_categories_display_option()
                    )->set_courses_display_options($this->get_courses_display_option()
                    )->set_display_options($this->get_display_option());
        }
        return $childcategories;
    }

    /**
     * Returns the count of the child categories
     *
     * @return int
     */
    function get_child_categories_count() {
        return $this->get_category()->get_children_count();
    }

    /**
     * Returns array of courses in this category
     *
     * @return array of rows from DB {courses} table
     */
    public function get_child_courses() {
        if ($this->get_show_courses() == coursecat_renderable::SHOW_COURSES_NONE) {
            return array();
        }
        $fullinfo = $this->get_show_courses() >= coursecat_renderable::SHOW_COURSES_EXPANDED;
        $displayoptions = array('recursive' => $this->get_omit_subcat(),
                    'enrolledonly' => $this->get_show_enrolled_only(),
                    'summary' => $fullinfo,
                    'coursecontacts' => $fullinfo
                ) +
                array_intersect_key($this->get_courses_display_option(),
                    array('sort' => 1, 'offset' => 1, 'limit' => 1));
        if (empty($this->searchcriteria)) {
            $childcourses = $this->get_category()->get_courses($displayoptions);
        } else {
            $childcourses = $this->get_category()->search_courses($this->searchcriteria, $displayoptions);
        }
        return $childcourses;
    }

    /**
     * Returns the number of visible courses in this category
     *
     * @return int
     */
    public function get_child_courses_count() {
        $options = array('recursive' => $this->get_omit_subcat(),
                    'enrolledonly' => $this->get_show_enrolled_only());
        if (empty($this->searchcriteria)) {
            return $this->get_category()->get_courses_count($options);
        } else {
            return $this->get_category()->search_courses_count($this->searchcriteria, $options);
        }
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
        $context = context_course::instance($course->id);
        if (!isset($options['context'])) {
            $options['context'] = $context;
        }
        $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
        $summary = format_text($summary, $course->summaryformat, $options, $course->id);
        if (!empty($this->searchcriteria['search'])) {
            $summary = highlight($this->searchcriteria['search'], $summary);
        }
        return $summary;
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
        $name = format_string($name, true, $options);
        if (!empty($this->searchcriteria['search'])) {
            $name = highlight($this->searchcriteria['search'], $name);
        }
        return $name;
    }
}
