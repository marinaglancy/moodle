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
    'core/ajax',
    'core/notification',
    'core/templates',
    'core/event',
    'core/str',
    'core/yui',
    'core/fragment'
], function($, Ajax, Notification, Templates, Event, Str, Y, Fragment) {
    /**
     * Constructor
     *
     * Creates an instance
     *
     * @param {jQuery|String|Element} container - the element that wraps the <form> element
     * @param {string} formClass full name of the php class that extends \core_form\modal , must be in autoloaded location
     */
    var AjaxForm = function(container, formClass) {
        this.formClass = formClass;
        this.container = container;
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
    AjaxForm.prototype.init = async function() {
        var element,
            selectorPrefix = '';
        if (typeof this.container === 'object') {
            // The container is an element on the page. Register a listener for this element.
            element = $(this.container);
        } else {
            // The container is a CSS selector. Register a listener that picks up the element dynamically.
            element = $('body');
            selectorPrefix = this.container + ' ';
        }

        // Ensure strings required for shortforms are always available.
        await Str.get_strings([
            {key: 'collapseall', component: 'moodle'},
            {key: 'expandall', component: 'moodle'}
        ]).catch(Notification.exception);

        // We catch the press on submit buttons in the forms.
        element.on('click', selectorPrefix + 'form input[type=submit][data-cancel]', this.cancelButtonPressed.bind(this));

        // We catch the press on submit buttons in the forms.
        element.on('click', selectorPrefix + 'form input[type=submit][data-no-submit]', this.noSubmitButtonPressed.bind(this));

        // We catch the form submit event and use it to submit the form with ajax.
        element.on('submit', selectorPrefix + 'form', this.submitFormAjax.bind(this));
    };

    /**
     * Gets a container that corresponds to the given element
     *
     * @param {jQuery|Element} element
     * @return {jQuery}
     */
    AjaxForm.prototype.getContainer = function(element) {
        if (typeof this.container === 'object') {
            // The container is an element on the page.
            return $(this.container);
        } else {
            // The container is a CSS selector.
            return $(element).closest(this.container);
        }
    };

    /**
     * Loads the form via AJAX and shows it inside a given container
     *
     * @param {Object} args
     * @return {Promise}
     * @public
     */
    AjaxForm.prototype.load = function(args) {
        var container = $(this.container);
        return this.getBody($.param(args)).then(function(html, js) {
            this.updateForm(container, html, js);
        }.bind(this));
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
            })
            .fail(function(ex) {
                promise.reject(ex);
            });
        return promise.promise();
    };

    /**
     * On form submit. Caller may override
     *
     * @param {Object} response Response received from the form's "process" method
     * @param {jQuery} container
     * @return {Object}
     */
    AjaxForm.prototype.onSubmitSuccess = function(response, container) {
        // Remove the form since its contents is no longer correct. For example, if the element was created as a result of
        // form submission the "id" in the form will be still zero.
        container.html('');

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
     * @param {jQuery} container
     */
    AjaxForm.prototype.onCancel = function(container) {
        // By default removes the form from the DOM.
        container.html('');
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

        var container = this.getContainer(e.currentTarget);
        Event.notifyFormSubmitAjax(container.find('form')[0], true);

        // Add the button name to the form data and submit it.
        var formData = container.find('form').serialize(),
            el = $(e.currentTarget);
        formData = formData + '&' + encodeURIComponent(el.attr('name')) + '=' + encodeURIComponent(el.attr('value'));
        this.disableButtons(container);
        this.getBody(formData)
            .then(function(html, js) {
                this.updateForm(container, html, js);
            }.bind(this))
            .fail(Notification.exception);
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
        var container = this.getContainer(e.currentTarget);
        Event.notifyFormSubmitAjax(container.find('form')[0], true);

        // Reset "dirty" form state (warning if there are changes).
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });

        this.onCancel(container);
    };

    /**
     * Update form contents
     *
     * @param {jQuery} container
     * @param {string} html
     * @param {string} js
     */
    AjaxForm.prototype.updateForm = function(container, html, js) {
        Templates.replaceNodeContents(container, html, js);
    };

    /**
     * Validate form elements
     * @param {jQuery} container
     * @return {boolean} true if client-side validation has passed, false if there are errors
     */
    AjaxForm.prototype.validateElements = function(container) {

        // Notify listeners that the form is about to be submitted (this will reset atto autosave).
        Event.notifyFormSubmitAjax(container.find('form')[0]);

        // Now the change events have run, see if there are any "invalid" form fields.
        var invalid = $.merge(
            container.find('[aria-invalid="true"]'),
            container.find('.error')
        );

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid.first().focus();
            return false;
        }

        return true;
    };

    /**
     * Disable buttons during form submission
     * @param {jQuery} container
     * @private
     */
    AjaxForm.prototype.disableButtons = function(container) {
        container.find('form input[type="submit"]').attr('disabled', true);
    };

    /**
     * Enable buttons after form submission (on validation error)
     * @param {jQuery} container
     * @private
     */
    AjaxForm.prototype.enableButtons = function(container) {
        container.find('form input[type="submit"]').removeAttr('disabled');
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
        var container = this.getContainer(e.currentTarget);

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (!this.validateElements(container)) {
            return;
        }
        this.disableButtons(container);

        // Convert all the form elements values to a serialised string.
        var formData = container.find('form').serialize();

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
                    this.updateForm(container, response.html, Fragment.processCollectedJavascript(response.javascript));
                    this.enableButtons(container);
                    this.onValidationError();
                } else {
                    // Form was submitted properly.
                    // Reset "dirty" form state (warning if there are changes).
                    Y.use('moodle-core-formchangechecker', function() {
                        M.core_formchangechecker.reset_form_dirty_state();
                    });

                    // Execute callback.
                    var data = JSON.parse(response.data);
                    this.enableButtons(container);
                    this.onSubmitSuccess(data, container);
                }
                return null;
            }.bind(this))
            .fail(this.onSubmitError.bind(this));
    };

    return AjaxForm;
});
