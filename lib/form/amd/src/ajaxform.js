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
 * Display an embedded form, it is only loaded and reloaded inside its container
 *
 * @module     core_form/ajaxform
 * @package    core_form
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/fragment',
    'core/ajax',
    'core/notification',
    'core/templates',
    'core/event',
    'core/yui'
], function($, Fragment, Ajax, Notification, Templates, Event, Y) {
    /**
     * Constructor
     *
     * Loads the form via AJAX and shows it inside a given container
     *
     * @param {jQuery} container
     * @param {string} formClass
     * @param {Object} args
     */
    var AjaxForm = function(container, formClass, args) {
        this.formClass = formClass;
        this.container = container;
        this.getBody($.param(args)).then(this.updateForm.bind(this));
        this.init();
    };

    /**
     * @var {jQuery} container
     */
    AjaxForm.prototype.container = null;

    /**
     * @var {string} formClass
     */
    AjaxForm.prototype.formClass = '';

    /**
     * Initialise listeners.
     *
     * @private
     */
    AjaxForm.prototype.init = function() {

        // We catch the press on submit buttons in the forms.
        this.container.on('click', 'form input[type=submit][data-cancel]', this.cancelButtonPressed.bind(this));

        // We catch the press on submit buttons in the forms.
        this.container.on('click', 'form input[type=submit][data-no-submit]', this.noSubmitButtonPressed.bind(this));

        // We catch the form submit event and use it to submit the form with ajax.
        this.container.on('submit', 'form', this.submitFormAjax.bind(this));
    };

    /**
     * @param {String} formDataString form data in format of a query string
     * @method getBody
     * @private
     * @return {Promise}
     */
    AjaxForm.prototype.getBody = function(formDataString) {
        var promise = $.Deferred();
        Ajax.call([{
            methodname: 'core_form_modal',
            args: {
                formdata: formDataString,
                form: this.formClass
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
    AjaxForm.prototype.onSubmitSuccess = function(response) {
        // By default removes the form.
        this.container.html('');
        // Return here is irrelevant, it is only present to make eslint happy.
        return response;
    };

    /**
     * On form validation error. Caller may override
     *
     * @return {mixed}
     */
    AjaxForm.prototype.onValidationError = function() {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return undefined;
    };

    /**
     * On form cancel. Caller may override
     */
    AjaxForm.prototype.onCancel = function() {
        // By default removes the form.
        this.container.html('');
    };

    /**
     * On exception during form processing. Caller may override
     *
     * @param {Object} exception
     */
    AjaxForm.prototype.onSubmitError = function(exception) {
        Notification.exception(exception);
    };

    /**
     * Click on a "submit" button that is marked in the form as registerNoSubmitButton()
     *
     * @method submitButtonPressed
     * @private
     * @param {Event} e Form submission event.
     */
    AjaxForm.prototype.noSubmitButtonPressed = function(e) {
        e.preventDefault();

        // Save TinyMCE editor data.
        if (typeof window.tinyMCE !== 'undefined') {
            window.tinyMCE.triggerSave();
        }

        // Add the button name to the form data and submit it.
        var formData = this.container.find('form').serialize(),
            el = $(e.currentTarget);
        formData = formData + '&' + encodeURIComponent(el.attr('name')) + '=' + encodeURIComponent(el.attr('value'));
        this.getBody(formData).then(this.updateForm.bind(this));
    };

    /**
     * Click on a "cancel" button
     *
     * @method cancelButtonPressed
     * @private
     * @param {Event} e Form submission event.
     */
    AjaxForm.prototype.cancelButtonPressed = function(e) {
        e.preventDefault();

        // Notify listeners that the form is about to be submitted (this will reset atto autosave).
        Event.notifyFormSubmitAjax(this.container.find('form')[0]);

        // Reset "dirty" form state (warning if there are changes).
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });

        this.onCancel();
    };

    /**
     * Update form contents
     *
     * @param {string} html
     * @param {string} js
     */
    AjaxForm.prototype.updateForm = function(html, js) {
        this.container.html(html);
        if (js) {
            Templates.runTemplateJS(js);
        }
    };

    /**
     * Validate form elements
     * @return {boolean} true if client-side validation has passed, false if there are errors
     */
    AjaxForm.prototype.validateElements = function() {
        var changeEvent = document.createEvent('HTMLEvents');
        changeEvent.initEvent('change', true, true);

        // Prompt all inputs to run their validation functions.
        // Normally this would happen when the form is submitted, but
        // since we aren't submitting the form normally we need to run client side
        // validation.
        this.container.find(':input').each(function(index, element) {
            element.dispatchEvent(changeEvent);
        });

        // Now the change events have run, see if there are any "invalid" form fields.
        var invalid = $.merge(
            this.container.find('[aria-invalid="true"]'),
            this.container.find('.error')
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
    AjaxForm.prototype.submitFormAjax = function(e) {
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

        // Notify listeners that the form is about to be submitted (this will reset atto autosave).
        Event.notifyFormSubmitAjax(this.container.find('form')[0]);

        // Convert all the form elements values to a serialised string.
        var formData = this.container.find('form').serialize();

        // Now we can continue...
        Ajax.call([{
            methodname: 'core_form_modal',
            args: {
                formdata: formData,
                form: this.formClass
            }
        }])[0]
            .then(function(response) {
                if (!response.submitted) {
                    // Form was not submitted, it could be either because validation failed or because no-submit button was pressed.
                    this.updateForm(response.html, Fragment.processCollectedJavascript(response.javascript));
                    this.onValidationError();
                } else {
                    // Form was submitted properly. Execute callback.
                    var data = JSON.parse(response.data);
                    this.onSubmitSuccess(data);
                }
            }.bind(this))
            .fail(this.onSubmitError.bind(this));
    };

    return AjaxForm;
});
