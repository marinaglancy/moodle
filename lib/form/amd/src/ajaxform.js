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

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Event from 'core/event';
import {get_strings as getStrings} from 'core/str';
import Y from 'core/yui';
import Fragment from 'core/fragment';

export default class AjaxForm {
    /**
     * Constructor
     *
     * Creates an instance
     *
     * @param {String|Element} container - the element that wraps the <form> element
     * @param {string} formClass full name of the php class that extends \core_form\modal , must be in autoloaded location
     */
    constructor(container, formClass) {
        this.formClass = formClass;
        this.container = container;
        this.init();
    }

    /**
     * Initialise listeners.
     *
     * @private
     */
    async init() {
        var element,
            selectorPrefix = '';
        if (typeof this.container === 'object') {
            // The container is an element on the page. Register a listener for this element.
            element = this.container;
        } else {
            // The container is a CSS selector. Register a listener that picks up the element dynamically.
            element = document;
            selectorPrefix = this.container + ' ';
        }

        // Ensure strings required for shortforms are always available.
        await getStrings([
            {key: 'collapseall', component: 'moodle'},
            {key: 'expandall', component: 'moodle'}
        ]).catch(Notification.exception);

        // Allow to register delegated events handlers in vanilla JS (similar to Jquery .on()).
        const on = (parentElement, eventType, childSelector, eventHandler) => {
            parentElement.addEventListener(eventType, eventOnElement => {
                if (eventOnElement.target.matches(childSelector)) {
                    eventHandler(eventOnElement);
                }
            });
        };

        // We catch the press on cancel button in the form.
        on(element, 'click', selectorPrefix + 'form input[type=submit][data-cancel]', this.cancelButtonPressed.bind(this));

        // We catch the press on no-submit buttons in the forms (for example, "Add" button in the repeat element).
        on(element, 'click', selectorPrefix + 'form input[type=submit][data-no-submit]', this.noSubmitButtonPressed.bind(this));

        // We catch the form submit event and use it to submit the form with ajax.
        on(element, 'submit', selectorPrefix + 'form', this.submitFormAjax.bind(this));
    }

    /**
     * Gets a container that contains the given element
     *
     * @param {Element} childElement
     * @return {Element}
     */
    findContainerElement(childElement = null) {
        if (typeof this.container === 'object') {
            // The container is an element on the page.
            return this.container;
        } else if (childElement) {
            // The container is a CSS selector.
            return childElement.closest(this.container);
        } else {
            return document.querySelector(this.container);
        }
    }

    /**
     * Loads the form via AJAX and shows it inside a given container
     *
     * @param {Object} args
     * @return {Promise}
     * @public
     */
    load(args) {
        const formData = Object.keys(args || {})
            .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(args[k]))
            .join('&');
        return this.getBody(formData)
            .then(([html, js]) => this.updateForm(this.findContainerElement(), html, js));
    }

    /**
     * @param {String} formDataString form data in format of a query string
     * @method getBody
     * @private
     * @return {Promise}
     */
    getBody(formDataString) {
        return new Promise((resolve, reject) => {
            Ajax.call([{
                methodname: 'core_form_modal',
                args: {
                    formdata: formDataString,
                    form: this.formClass
                }
            }])[0]
                .then(response => resolve([response.html, Fragment.processCollectedJavascript(response.javascript)]))
                .catch(ex => reject(ex));
        });
    }

    /**
     * On form submit. Caller may override
     *
     * @param {Object} response Response received from the form's "process" method
     * @param {Element} container
     * @return {Object}
     */
    onSubmitSuccess(response, container) {
        // Remove the form since its contents is no longer correct. For example, if the element was created as a result of
        // form submission the "id" in the form will be still zero.
        container.innerHTML = '';

        // Return here is irrelevant, it is only present to make eslint happy.
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
     * On form cancel. Caller may override
     * @param {Element} container
     */
    onCancel(container) {
        // By default removes the form from the DOM.
        container.innerHTML = '';
    }

    /**
     * On exception during form processing. Caller may override
     *
     * @param {Object} exception
     */
    onSubmitError(exception) {
        Notification.exception(exception);
    }

    serializeForm(form) {
        var formData = new FormData(form);
        return [...formData.keys()]
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(formData.get(key)))
            .join('&');
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

        let button = e.target;
        var container = this.findContainerElement(button);
        await this.notifyFormSubmitAjax(container.querySelector('form'), true);

        // Add the button name to the form data and submit it.
        const formData = this.serializeForm(container.querySelector('form')) + '&' +
            encodeURIComponent(button.getAttribute('name')) + '=' +
            encodeURIComponent(button.getAttribute('value'));
        this.disableButtons(container);
        this.getBody(formData)
            .then(([html, js]) => this.updateForm(container, html, js))
            .catch(Notification.exception);
    }

    /**
     * Wrapper for Event.notifyFormSubmitAjax that waits for the module to load
     *
     * We often destroy the form right after calling this function and we need to make sure that it actually
     * completes before it, or otherwise it will try to work with a form that does not exist.
     *
     * @param {Element} form
     * @param {Boolean} skipValidation
     */
    async notifyFormSubmitAjax(form, skipValidation = false) {
        let promise = new Promise(resolve => {
            Y.use('event', 'moodle-core-event', 'moodle-core-formchangechecker', function() {
                Event.notifyFormSubmitAjax(form, skipValidation);
                resolve();
            });
        });
        await promise;
    }

    /**
     * Click on a "cancel" button
     *
     * @method cancelButtonPressed
     * @private
     * @param {Event} e Form submission event.
     */
    async cancelButtonPressed(e) {
        e.preventDefault();

        const container = this.findContainerElement(e.target),
            form = container.querySelector('form');
        // Notify listeners that the form is about to be submitted (this will reset atto autosave).
        await this.notifyFormSubmitAjax(form, true);
        // Reset "dirty" form state (warning if there are changes).
        M.core_formchangechecker.reset_form_dirty_state();

        this.onCancel(container);
    }

    /**
     * Update form contents
     *
     * @param {Element} container
     * @param {string} html
     * @param {string} js
     */
    updateForm(container, html, js) {
        Templates.replaceNodeContents(container, html, js);
    }

    /**
     * Validate form elements
     * @param {Element} container
     * @return {boolean} true if client-side validation has passed, false if there are errors
     */
    async validateElements(container) {

        // Notify listeners that the form is about to be submitted (this will reset atto autosave).
        await this.notifyFormSubmitAjax(container.querySelector('form'));

        // Now the change events have run, see if there are any "invalid" form fields.
        const invalid = [...container.querySelectorAll('[aria-invalid="true"], .error')];

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid[0].focus();
            return false;
        }

        return true;
    }

    /**
     * Disable buttons during form submission
     * @param {Element} container
     * @private
     */
    disableButtons(container) {
        [...container.querySelectorAll('form input[type="submit"]')]
            .map(el => el.setAttribute('disabled', true));
    }

    /**
     * Enable buttons after form submission (on validation error)
     * @param {Element} container
     * @private
     */
    enableButtons(container) {
        [...container.querySelectorAll('form input[type="submit"]')]
            .map(el => el.removeAttribute('disabled'));
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
        let container = this.findContainerElement(e.target);

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (!(await this.validateElements(container))) {
            return;
        }
        this.disableButtons(container);

        // Convert all the form elements values to a serialised string.
        var formData = this.serializeForm(container.querySelector('form'));

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
    }
}
