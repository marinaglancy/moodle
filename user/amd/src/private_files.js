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
 * Module to handle AJAX interactions with user private files
 *
 * @module     core_user/private_files
 * @copyright  2020 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import AjaxForm from 'core_form/ajaxform';
import ModalForm from 'core_form/modal';
import {get_string as getString} from 'core/str';

/**
 * Initialize private files form as AJAX form
 *
 * @param {String} containerSelector
 * @param {String} formClass
 */
export const initAjax = (containerSelector, formClass) => {
    const form = new AjaxForm(document.querySelector(containerSelector), formClass);
    // Load the private files form immediately when page is displayed:
    form.load();
    // When form is saved, refresh it to remove validation errors, if any:
    form.onSubmitSuccess = () => form.load();
};

/**
 * Initialize private files form as Modal form
 *
 * @param {String} elementSelector
 * @param {String} formClass
 */
export const initModal = (elementSelector, formClass) => {
    document.querySelector(elementSelector).addEventListener('click', function(e) {
        e.preventDefault();
        const form = new ModalForm({
            formClass,
            args: {nosubmit: true},
            modalConfig: {title: getString('privatefilesmanage')},
            returnFocus: e.target,
        });
        form.onSubmitSuccess = () => window.location.reload();
    });
};
