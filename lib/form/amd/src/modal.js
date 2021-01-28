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
 * Display a form in a modal dialogue
 *
 * @module     core_form/modal
 * @package    core_form
 * @copyright  2018 Mitxel Moriana <mitxel@tresipunt.>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Y from 'core/yui';
import Event from 'core/event';
import {get_strings as getStrings} from 'core/str';
import Fragment from 'core/fragment';

export default class ModalForm {
    /**
     * Constructor
     *
     * Shows the required form inside a modal dialogue
     *
     * @param {Object} config Parameters for the list. See defaultParameters above for examples.
     * @property {String} config.formClass PHP class name that handles the form (should extend \core_form\modal )
     * @property {Object} config.modalConfig modal config - title, type, etc. By default type is set
     *              to ModalFactory.types.SAVE_CANCEL and removeOnClose is true
     * @property {Object} config.args Arguments for the initial form rendering
     * @property {String} config.saveButtonText the text to display on the Modal "Save" button (optional)
     * @property {String} config.saveButtonClasses additional CSS classes for the Modal "Save" button
     * @property {HTMLElement} config.returnFocus element to return focus to after the dialogue is closed
     */
    constructor(config) {
        this.modal = null;
        this.config = config;
        this.config.modalConfig = {removeOnClose: true, type: ModalFactory.types.SAVE_CANCEL,
            ...(this.config.modalConfig || {})};
        this.config.args = this.config.args || {};
        this.init();
    }

    /**
     * Initialise the class.
     *
     * @private
     */
    init() {
        var requiredStrings = [
            {key: 'collapseall', component: 'moodle'},
            {key: 'expandall', component: 'moodle'}
        ];

        // Ensure strings required for shortforms are always available.
        M.util.js_pending('core_form_modal_form_init');
        getStrings(requiredStrings)
            .then(function() {
                return ModalFactory.create(this.config.modalConfig);
            }.bind(this))
            .then(function(modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // We need to make sure that the modal already exists when we render the form. Some form elements
                // such as date_selector inspect the existing elements on the page to find the highest z-index.
                const formString = Object.keys(this.config.args)
                    .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(this.config.args[k]))
                    .join('&');
                this.modal.setBody(this.getBody(formString));

                // Forms are big, we want a big modal.
                this.modal.setLarge();

                // After successfull submit, when we press "Cancel" or close the dialogue by clicking on X in the top right corner.
                this.modal.getRoot().on(ModalEvents.hidden, () => {
                    // Reset form-change-checker.
                    this.resetDirtyFormState();
                    // Notify listeners that the form is about to be submitted (this will reset atto autosave).
                    this.notifyFormSubmitAjax(true)
                        .then(() => {
                            this.modal.destroy();
                            // Focus on the element that actually launched the modal.
                            if (this.config.returnFocus) {
                                this.config.returnFocus.focus();
                            }
                            return null;
                        });
                });

                // Add the class to the modal dialogue.
                this.modal.getModal().addClass('tool-wp-modal-form-dialogue');

                // We catch the press on submit buttons in the forms.
                this.modal.getRoot().on('click', 'form input[type=submit][data-no-submit]',
                    this.noSubmitButtonPressed.bind(this));

                // We catch the form submit event and use it to submit the form with ajax.
                this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                // Change the text for the save button.
                if (typeof this.config.saveButtonText !== 'undefined' &&
                    typeof this.modal.setSaveButtonText !== 'undefined') {
                    this.modal.setSaveButtonText(this.config.saveButtonText);
                }
                // Set classes for the save button.
                if (typeof this.config.saveButtonClasses !== 'undefined') {
                    this.setSaveButtonClasses(this.config.saveButtonClasses);
                }
                // Register Other button callback when this type is used.
                if (this.config.modalConfig.type === "SAVE_CANCEL_OTHER") {
                    this.modal.registerCloseOnOther(this.registerCloseOnOther);
                }
                this.onInit();

                this.modal.show();
                M.util.js_complete('core_form_modal_form_init');
                return this.modal;
            }.bind(this))
            .fail(Notification.exception);
    }

    /**
     * On initialisation of a modal dialogue. Caller may override.
     */
    onInit() {
        // We catch the modal save event, and use it to submit the form inside the modal.
        // Triggering a form submission will give JS validation scripts a chance to check for errors.
        this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
    }

    /**
     * Callback function for third button when type is SAVE_CANCEL_OTHER. Caller may override.
     * @param {Function} callback
     * @returns {Function} callback
     */
    registerCloseOnOther(callback) {
        return callback;
    }

    /**
     * Get form contents (to be used in ModalForm.setBody())
     *
     * @param {String} formDataString form data in format of a query string
     * @method getBody
     * @private
     * @return {Promise}
     */
    getBody(formDataString) {
        const params = {
            formdata: formDataString,
            form: this.config.formClass
        };
        M.util.js_pending('core_form_modal_form_body');
        return Ajax.call([{
            methodname: 'core_form_modal',
            args: params
        }])[0]
            .then(response => {
                M.util.js_complete('core_form_modal_form_body');
                return [response.html, Fragment.processCollectedJavascript(response.javascript)];
            });
    }

    /**
     * On form submit. Caller may override
     *
     * @param {Object} response Response received from the form's "process" method
     * @return {Object}
     */
    onSubmitSuccess(response) {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return response;
    }

    /**
     * On form validation error. Caller may override
     *
     * @return {mixed}
     */
    onValidationError() {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return undefined;
    }

    /**
     * On exception during form processing. Caller may override
     *
     * @param {Object} exception
     */
    onSubmitError(exception) {
        Notification.exception(exception);
    }

    /**
     * Reset "dirty" form state (warning if there are changes)
     */
    resetDirtyFormState() {
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
    }

    /**
     * Wrapper for Event.notifyFormSubmitAjax that waits for the module to load
     *
     * We often destroy the form right after calling this function and we need to make sure that it actually
     * completes before it, or otherwise it will try to work with a form that does not exist.
     *
     * @param {Boolean} skipValidation
     */
    notifyFormSubmitAjax(skipValidation = false) {
        let promise = new Promise(resolve => {
            Y.use('event', 'moodle-core-event', 'moodle-core-formchangechecker', () => {
                Event.notifyFormSubmitAjax(this.modal.getRoot().find('form')[0], skipValidation);
                resolve();
            });
        });
        return promise;
    }

    /**
     * Click on a "submit" button that is marked in the form as registerNoSubmitButton()
     *
     * @method submitButtonPressed
     * @private
     * @param {Event} e Form submission event.
     */
    async noSubmitButtonPressed(e) {
        e.preventDefault();
        const target = e.currentTarget;

        await this.notifyFormSubmitAjax(true);

        // Add the button name to the form data and submit it.
        let formData = this.modal.getRoot().find('form').serialize();
        formData = formData + '&' + encodeURIComponent(target.getAttribute('name')) + '=' +
            encodeURIComponent(target.getAttribute('value'));
        this.modal.setBody(this.getBody(formData));
    }

    /**
     * Validate form elements
     * @return {boolean} true if client-side validation has passed, false if there are errors
     */
    async validateElements() {
        await this.notifyFormSubmitAjax();

        // Now the change events have run, see if there are any "invalid" form fields.
        /** @var {jQuery} list of elements with errors */
        const invalid = this.modal.getRoot().find('[aria-invalid="true"], .error');

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid.first().focus();
            return false;
        }

        return true;
    }

    /**
     * Disable buttons during form submission
     */
    disableButtons() {
        this.modal.getFooter().find('[data-action]').attr('disabled', true);
    }

    /**
     * Enable buttons after form submission (on validation error)
     */
    enableButtons() {
        this.modal.getFooter().find('[data-action]').removeAttr('disabled');
    }

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    async submitFormAjax(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (!await this.validateElements()) {
            return;
        }
        this.disableButtons();

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();

        // Now we can continue...
        Ajax.call([{
            methodname: 'core_form_modal',
            args: {
                formdata: formData,
                form: this.config.formClass
            }
        }])[0]
            .then(function(response) {
                if (!response.submitted) {
                    // Form was not submitted, it could be either because validation failed or because no-submit button was pressed.
                    const promise = new Promise(
                        resolve => resolve(response.html, Fragment.processCollectedJavascript(response.javascript)));
                    this.modal.setBody(promise);
                    this.enableButtons();
                    this.onValidationError();
                } else {
                    // Form was submitted properly. Hide the modal and execute callback.
                    var data = JSON.parse(response.data);
                    this.modal.hide();
                    this.onSubmitSuccess(data);
                }
                return null;
            }.bind(this))
            .fail(this.onSubmitError.bind(this));
    }

    /**
     * This triggers a form submission, so that any mform elements can do final tricks
     * before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    submitForm(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    }

    /**
     * Set the classes for the 'save' button.
     *
     * @method setSaveButtonClasses
     * @param {(String)} value The 'save' button classes.
     */
    setSaveButtonClasses(value) {
        const button = this.modal.getFooter().find("[data-action='save']");
        if (!button) {
            throw new Error("Unable to find the 'save' button");
        }
        button.removeClass().addClass(value);
    }
}
