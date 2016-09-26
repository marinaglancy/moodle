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
 * Helper to prepare file types for admin setting and form control use.
 *
 * @package   core_form
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_form;

defined('MOODLE_INTERNAL') || die;

use core_collator,
    core_filetypes,
    stdClass;

/**
 * A helper to prepare file types for admin setting and form control use.
 *
 * @package   core_form
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filetypes {
    /**
     * The names of type groups, localised and sorted by name.
     * Each entry keyed by group identifier has:
     *   name (string) => group name
     *   types (array) => group mimetypes
     *   extlist (string) => extensions (optional)
     *   isoption (boolean) => true if the entire group is a choice (optional)
     *
     * @var array of objects
     */
    private $typegroups = array();

    /**
     * The names of file types, localised and sorted by name.
     * Each entry keyed by mimetype has:
     *   name (string) => type name
     *   extlist (string) => extensions
     *
     * @var array of objects
     */
    private $alltypes = array();

    /**
     * Returns the set of types groups.
     * @return array of objects
     */
    public function get_typegroups() {
        return $this->typegroups;
    }

    /**
     * Returns all the file types in all type groups.
     * @return array of objects
     */
    public function get_alltypes() {
        return $this->alltypes;
    }

    /**
     * Constructor
     *
     * @param array $limitto if not empty, the mimetypes and/or groups to restrict to
     */
    public function __construct($limitto = array()) {
        // Initialise the types and groups, lexically sorted.
        list ($groups, $other) = $this->get_all_typegroups();

        foreach ($groups as $group) {
            $this->typegroups[$group] = (object)array(
                'name' => get_string("group:$group", 'mimetypes'),
                'types' => array(),
                'extlist' => implode(' ', file_get_typegroup('extension', $group)),
                'isoption' => !$limitto || in_array($group, $limitto),
            );
            foreach (file_get_typegroup('type', $group) as $type) {
                if ($this->typegroups[$group]->isoption || in_array($type, $limitto)) {
                    $this->add_type($type, $group);
                }
            }

            if (!$this->typegroups[$group]->isoption) {
                // If the type group is not a selectable option, we don't
                // need to return its extensions list.
                unset($this->typegroups[$group]->extlist);
            }

            if (empty($this->typegroups[$group]->types)) {
                // If the type group does not contain any types, we don't
                // need to return it.
                unset($this->typegroups[$group]);
            }
        }

        core_collator::asort_objects_by_property($this->typegroups, 'name', core_collator::SORT_NATURAL);

        if ($other) {
            // Add the ungrouped types to an 'other' group at the end of the list.
            $this->typegroups['-'] = (object)array(
                'name' => get_string('otherfiles', 'form'),
                'types' => array(),
                'isoption' => false,
            );
            foreach ($other as $type) {
                if ($limitto && !in_array($type, $limitto)) {
                    continue;
                }
                $this->add_type($type, '-');
            }

            if (empty($this->typegroups['-']->types)) {
                // If the type group does not contain any types, we don't
                // need to return it.
                unset($this->typegroups['-']);
            }
        }

        core_collator::asort_objects_by_property($this->alltypes, 'name', core_collator::SORT_NATURAL);
    }

    /**
     * Add a type to a type group.
     * @param string $type mime type
     * @param string $group group identifier
     */
    private function add_type($type, $group) {
        $exts = file_get_typegroup('extension', $type);
        $mtype = array(
            'filename' => '.'.$exts[0],
            'mimetype' => $type,
        );
        $a = new stdClass();
        $a->extlist = implode(' ', $exts);
        $a->name = get_mimetype_description($mtype);

        $this->alltypes[$type] = $a;
        $this->typegroups[$group]->types[] = $type;
    }

    /**
     * Fetch all the known typegroups.
     * @return array (typegroups, other ungrouped types)
     */
    private function get_all_typegroups() {
        $typegroups = array();
        $other = array();
        foreach (core_filetypes::get_types() as $info) {
            if (!empty($info['groups'])) {
                $typegroups = array_merge($typegroups, $info['groups']);
            } else {
                $other[] = $info['type'];
            }
        }
        return array(array_unique($typegroups), array_unique($other));
    }
}
