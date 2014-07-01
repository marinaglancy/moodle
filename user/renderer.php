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
 * Provides user rendering functionality such as printing private files tree and displaying a search utility
 *
 * @package    core_user
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides user rendering functionality such as printing private files tree and displaying a search utility
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_user_renderer extends plugin_renderer_base {

    /**
     * Prints user files tree view
     * @return string
     */
    public function user_files_tree() {
        return $this->render(new user_files_tree);
    }

    /**
     * Render user files tree
     *
     * @param user_files_tree $tree
     * @return string HTML
     */
    public function render_user_files_tree(user_files_tree $tree) {
        if (empty($tree->dir['subdirs']) && empty($tree->dir['files'])) {
            $html = $this->output->box(get_string('nofilesavailable', 'repository'));
        } else {
            $htmlid = 'user_files_tree_'.uniqid();
            $module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
            $this->page->requires->js_init_call('M.core_user.init_tree', array(false, $htmlid), false, $module);
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree, $tree->dir);
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     * @param user_files_tree $tree
     * @param array $dir
     * @return string HTML
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(), $subdir['dirname'], 'moodle', array('class' => 'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.s($subdir['dirname']).'</div> '.
                $this->htmllize_tree($tree, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$tree->context->id.'/user/private'.
                $file->get_filepath().$file->get_filename(), true);
            $filename = $file->get_filename();
            $image = $this->output->pix_icon(file_file_icon($file), $filename, 'moodle', array('class' => 'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.html_writer::link($url, $filename).
                '</div></li>';
        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * Prints user search utility that can search user by first initial of firstname and/or first initial of lastname
     * Prints a header with a title and the number of users found within that subset
     * @param string $url the url to return to, complete with any parameters needed for the return
     * @param string $firstinitial the first initial of the firstname
     * @param string $lastinitial the first initial of the lastname
     * @param int $usercount the amount of users meeting the search criteria
     * @param int $totalcount the amount of users of the set/subset being searched
     * @param string $heading heading of the subset being searched, default is All Participants
     * @return string html output
     */
    public function user_search($url, $firstinitial, $lastinitial, $usercount, $totalcount, $heading = null) {
        global $OUTPUT;

        $strall = get_string('all');
        $alpha  = explode(',', get_string('alphabet', 'langconfig'));

        if (!isset($heading)) {
            $heading = get_string('allparticipants');
        }

        $content = html_writer::start_tag('form', array('action' => new moodle_url($url)));
        $content .= html_writer::start_tag('div');

        // Search utility heading.
        $content .= $OUTPUT->heading($heading.get_string('labelsep', 'langconfig').$usercount.'/'.$totalcount, 3);

        // Bar of first initials.
        $content .= html_writer::start_tag('div', array('class' => 'initialbar firstinitial'));
        $content .= html_writer::label(get_string('firstname').' : ', null);

        if (!empty($firstinitial)) {
            $content .= html_writer::link($url.'&sifirst=', $strall);
        } else {
            $content .= html_writer::tag('strong', $strall);
        }

        foreach ($alpha as $letter) {
            if ($letter == $firstinitial) {
                $content .= html_writer::tag('strong', $letter);
            } else {
                $content .= html_writer::link($url.'&sifirst='.$letter, $letter);
            }
        }
        $content .= html_writer::end_tag('div');

         // Bar of last initials.
        $content .= html_writer::start_tag('div', array('class' => 'initialbar lastinitial'));
        $content .= html_writer::label(get_string('lastname').' : ', null);

        if (!empty($lastinitial)) {
            $content .= html_writer::link($url.'&silast=', $strall);
        } else {
            $content .= html_writer::tag('strong', $strall);
        }

        foreach ($alpha as $letter) {
            if ($letter == $lastinitial) {
                $content .= html_writer::tag('strong', $letter);
            } else {
                $content .= html_writer::link($url.'&silast='.$letter, $letter);
            }
        }
        $content .= html_writer::end_tag('div');

        $content .= html_writer::end_tag('div');
        $content .= html_writer::tag('div', '&nbsp');
        $content .= html_writer::end_tag('form');

        return $content;
    }

    public function user_profile_fields_overview($additionalprofilecategories, $additionalprofilefields) {
        $rv = '';
        $reqhtml = '<img class="req" title="'.get_string('requiredelement', 'form').'" alt="'.get_string('requiredelement', 'form').'" src="'.$this->output->pix_url('req') .'" />';
        foreach ($additionalprofilecategories as $category) {
            $table = new html_table();
            $table->head  = array(get_string('profilefield', 'admin'), get_string('edit'));
            $table->align = array('left', 'right');
            $table->width = '95%';
            $table->attributes['class'] = 'generaltable profilefield';
            $table->data = array();

            $thiscategoryfields = array();
            foreach ($additionalprofilefields as $fieldid => $field) {
                if ($field->categoryid == $category->id) {
                    $thiscategoryfields[$fieldid] = $field;
                }
            }

            foreach ($thiscategoryfields as $field) {
                $table->data[] = array(format_string($field->name), $this->profile_field_icons($field, count($thiscategoryfields)));
            }

            $rv .= $this->output->heading(format_string($category->name) .' '.
                $this->profile_category_icons($category, count($additionalprofilecategories), count($thiscategoryfields)));
            if (count($table->data)) {
                $rv .= html_writer::table($table);
            } else {
                $strnofields = get_string('profilenofieldsdefined', 'admin');
                $rv .= $this->output->notification($strnofields);
            }

        } // End of $categories foreach.

        return $rv;
    }

    /**
     * Create a string containing the editing icons for the user profile categories
     * @param stdClass $category the category object
     * @param int $categorycount number of categories
     * @param int $fieldcount number of fields in this category
     * @return string the icon string
     */
    protected function profile_category_icons($category, $categorycount, $fieldcount) {
        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="index.php?id='.$category->id.'&amp;action=editcategory"><img src="'.$this->output->pix_url('t/edit') . '" alt="'.$stredit.'" class="iconsmall" /></a> ';

        // Delete.
        // Can only delete the last category if there are no fields in it.
        if (($categorycount > 1) or ($fieldcount == 0)) {
            $editstr .= '<a title="'.$strdelete.'" href="index.php?id='.$category->id.'&amp;action=deletecategory&amp;sesskey='.sesskey();
            $editstr .= '"><img src="'.$this->output->pix_url('t/delete') . '" alt="'.$strdelete.'" class="iconsmall" /></a> ';
        } else {
            $editstr .= '<img src="'.$this->output->pix_url('spacer') . '" alt="" class="iconsmall" /> ';
        }

        // Move up.
        if ($category->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" href="index.php?id='.$category->id.'&amp;action=movecategory&amp;dir=up&amp;sesskey='.sesskey().'"><img src="'.$this->output->pix_url('t/up') . '" alt="'.$strmoveup.'" class="iconsmall" /></a> ';
        } else {
            $editstr .= '<img src="'.$this->output->pix_url('spacer') . '" alt="" class="iconsmall" /> ';
        }

        // Move down.
        if ($category->sortorder < $categorycount) {
            $editstr .= '<a title="'.$strmovedown.'" href="index.php?id='.$category->id.'&amp;action=movecategory&amp;dir=down&amp;sesskey='.sesskey().'"><img src="'.$this->output->pix_url('t/down') . '" alt="'.$strmovedown.'" class="iconsmall" /></a> ';
        } else {
            $editstr .= '<img src="'.$this->output->pix_url('spacer') . '" alt="" class="iconsmall" /> ';
        }

        return $editstr;
    }

    /**
     * Create a string containing the editing icons for the user profile fields
     * @param stdClass $field the field object
     * @param int $fieldcount number of fields in the category
     * @return string the icon string
     */
    protected function profile_field_icons($field, $fieldcount) {
        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="index.php?id='.$field->id.'&amp;action=editfield"><img src="'.$this->output->pix_url('t/edit') . '" alt="'.$stredit.'" class="iconsmall" /></a> ';

        // Delete.
        $editstr .= '<a title="'.$strdelete.'" href="index.php?id='.$field->id.'&amp;action=deletefield&amp;sesskey='.sesskey();
        $editstr .= '"><img src="'.$this->output->pix_url('t/delete') . '" alt="'.$strdelete.'" class="iconsmall" /></a> ';

        // Move up.
        if ($field->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" href="index.php?id='.$field->id.'&amp;action=movefield&amp;dir=up&amp;sesskey='.sesskey().'"><img src="'.$this->output->pix_url('t/up') . '" alt="'.$strmoveup.'" class="iconsmall" /></a> ';
        } else {
            $editstr .= '<img src="'.$this->output->pix_url('spacer') . '" alt="" class="iconsmall" /> ';
        }

        // Move down.
        if ($field->sortorder < $fieldcount) {
            $editstr .= '<a title="'.$strmovedown.'" href="index.php?id='.$field->id.'&amp;action=movefield&amp;dir=down&amp;sesskey='.sesskey().'"><img src="'.$this->output->pix_url('t/down') . '" alt="'.$strmovedown.'" class="iconsmall" /></a> ';
        } else {
            $editstr .= '<img src="'.$this->output->pix_url('spacer') . '" alt="" class="iconsmall" /> ';
        }

        return $editstr;
    }
}

/**
 * User files tree
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_files_tree implements renderable {

    /**
     * @var context_user $context
     */
    public $context;

    /**
     * @var array $dir
     */
    public $dir;

    /**
     * Create user files tree object
     */
    public function __construct() {
        global $USER;
        $this->context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'user', 'private', 0);
    }
}
