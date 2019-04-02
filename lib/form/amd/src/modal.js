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
define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    'core/fragment',
    'core/ajax',
    'core/notification',
    'core/yui',
    'core/event'
], function($, ModalFactory, ModalEvents, Fragment, Ajax, Notification, Y, Event) {
    /**
     * Constructor
     *
     * Shows the required form inside a modal dialogue
     *
     * @param {Object} config Parameters for the list. See defaultParameters above for examples.
     * @property {String} config.formClass PHP class name that handles the form (should extend \core_form\modal )
     * @property {Object} config.modalConfig modal config - title, type, etc. By default type is set
     *              to ModalFactory.types.SAVE_CANCEL
     * @property {Object} config.args Arguments for the initial form rendering
     * @property {$} config.triggerElement trigger element for a modal form
     */
    var ModalForm = function(config) {
        this.config = config;
        this.config.modalConfig = this.config.modalConfig || {};
        this.config.modalConfig.type = this.config.modalConfig.type || ModalFactory.types.SAVE_CANCEL;
        this.config.args = this.config.args || {};
        this.init();
    };

    /**
     * @var {Object} config
     */
    ModalForm.prototype.config = {};

    /**
     * @var {Modal} modal
     */
    ModalForm.prototype.modal = null;

    /**
     * Initialise the class.
     *
     * @private
     */
    ModalForm.prototype.init = function() {
        ModalFactory.create(
            this.config.modalConfig,
            this.config.triggerElement)
            .then(function(modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // We need to make sure that the modal already exists when we render the form. Some form elements
                // such as date_selector inspect the existing elements on the page to find the highest z-index.
                this.modal.setBody(this.getBody($.param(this.config.args)));

                // Forms are big, we want a big modal.
                this.modal.setLarge();

                // After successfull submit, when we press "Cancel" or close the dialogue by clicking on X in the top right corner.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    // Notify listeners that the form is about to be submitted (this will reset atto autosave).
                    Event.notifyFormSubmitAjax(this.modal.getRoot().find('form')[0]);
                    // Destroy modal.
                    this.modal.destroy();
                    // Reset form-change-checker.
                    this.resetDirtyFormState();
                }.bind(this));

                // Add the class to the modal dialogue.
                this.modal.getModal().addClass('modal-form-dialogue');

                // We catch the press on submit buttons in the forms.
                this.modal.getRoot().on('click', 'form input[type=submit][data-no-submit]', this.noSubmitButtonPressed.bind(this));

                // We catch the form submit event and use it to submit the form with ajax.
                this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                // Change the text for the save button.
                if (typeof this.config.saveButtonText !== 'undefined' &&
                    typeof this.modal.setSaveButtonText !== 'undefined') {
                    this.modal.setSaveButtonText(this.config.saveButtonText);
                }

                this.onInit();

                this.modal.show();
                return this.modal;
            }.bind(this))
            .fail(Notification.exception);
    };

    /**
     * On initialisation of a modal dialogue. Caller may override.
     */
    ModalForm.prototype.onInit = function() {
        // We catch the modal save event, and use it to submit the form inside the modal.
        // Triggering a form submission will give JS validation scripts a chance to check for errors.
        this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
    };

    /**
     * @param {String} formDataString form data in format of a query string
     * @method getBody
     * @private
     * @return {Promise}
     */
    ModalForm.prototype.getBody = function(formDataString) {
        var promise = $.Deferred();
        Ajax.call([{
            methodname: 'core_form_modal',
            args: {
                formdata: formDataString,
                form: this.config.formClass
            }
        }])[0]
            .then(function(response) {
                promise.resolve(response.html, Fragment.processCollectedJavascript(response.javascript));
                return null;
            }).fail(function(ex) {
                promise.reject(ex);
            });
        return promise;
    };

    /**
     * On form submit. Caller may override
     *
     * @param {Object} response Response received from the form's "process" method
     * @return {Object}
     */
    ModalForm.prototype.onSubmitSuccess = function(response) {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return response;
    };

    /**
     * On form validation error. Caller may override
     *
     * @return {mixed}
     */
    ModalForm.prototype.onValidationError = function() {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return undefined;
    };

    /**
     * On exception during form processing. Caller may override
     *
     * @param {Object} exception
     */
    ModalForm.prototype.onSubmitError = function(exception) {
        Notification.exception(exception);
    };

    /**
     * Reset "dirty" form state (warning if there are changes)
     */
    ModalForm.prototype.resetDirtyFormState = function() {
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
    };

    /**
     * Click on a "submit" button that is marked in the form as registerNoSubmitButton()
     *
     * @method submitButtonPressed
     * @private
     * @param {Event} e Form submission event.
     */
    ModalForm.prototype.noSubmitButtonPressed = function(e) {
        e.preventDefault();

        // Save TinyMCE editor data.
        if (typeof window.tinyMCE !== 'undefined') {
            window.tinyMCE.triggerSave();
        }

        var formData = this.modal.getRoot().find('form').serialize(),
            el = $(e.currentTarget);
        formData = formData + '&' + encodeURIComponent(el.attr('name')) + '=' + encodeURIComponent(el.attr('value'));
        this.modal.setBody(this.getBody(formData));
    };

    /**
     * Validate form elements
     * @return {boolean} true if client-side validation has passed, false if there are errors
     */
    ModalForm.prototype.validateElements = function() {
        var changeEvent = document.createEvent('HTMLEvents');
        changeEvent.initEvent('change', true, true);

        // Prompt all inputs to run their validation functions.
        // Normally this would happen when the form is submitted, but
        // since we aren't submitting the form normally we need to run client side
        // validation.
        this.modal.getRoot().find(':input').each(function(index, element) {
            element.dispatchEvent(changeEvent);
        });

        // Now the change events have run, see if there are any "invalid" form fields.
        var invalid = $.merge(
            this.modal.getRoot().find('[aria-invalid="true"]'),
            this.modal.getRoot().find('.error')
        );

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid.first().focus();
            return false;
        }

        return true;
    };

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    ModalForm.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // Save TinyMCE editor data.
        if (typeof window.tinyMCE !== 'undefined') {
            window.tinyMCE.triggerSave();
        }

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (!this.validateElements()) {
            return;
        }

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
                    var promise = $.Deferred();
                    promise.resolve(response.html, Fragment.processCollectedJavascript(response.javascript));
                    this.modal.setBody(promise);
                    this.onValidationError();
                } else {
                    // Form was submitted properly. Hide the modal and execute callback.
                    var data = JSON.parse(response.data);
                    this.modal.hide();
                    this.onSubmitSuccess(data);
                }
            }.bind(this))
            .fail(this.onSubmitError.bind(this));
    };

    /**
     * This triggers a form submission, so that any mform elements can do final tricks
     * before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    ModalForm.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };

    return ModalForm;
});
