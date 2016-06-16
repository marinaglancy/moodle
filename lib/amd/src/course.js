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
 * Various actions on modules and sections in the editing mode - hiding, duplicating, deleting, etc.
 *
 * @module     core/course
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.2
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/url', 'core/yui'],
    function($, ajax, templates, notification, str, url, Y) {
        var CSS = {
            EDITINPROGRESS: 'editinprogress',
            SECTIONDRAGGABLE: 'sectiondraggable',
            EDITINGMOVE: 'editing_move'
        };
        var SELECTOR = {
            ACTIVITYLI: 'li.activity',
            ACTIONAREA: '.actions',
            ACTIVITYACTION: 'a.cm-edit-action[data-action]',
            MENU: '.moodle-actionmenu[data-enhance=moodle-core-actionmenu]',
            TOGGLE: '.toggle-display',
            SECTIONLI: 'li.section',
            SECTIONACTIONMENU: '.section_action_menu',
            HIGHLIGHT: 'a.editing_highlight',
            SHOWHIDE: 'a.editing_showhide'
        };

        Y.use('moodle-course-coursebase', function() {
            var courseformatselector = M.course.format.get_section_selector();
            if (courseformatselector) {
                SELECTOR.SECTIONLI = courseformatselector;
            }
        });

        /**
         * Wrapper for Y.Moodle.core_course.util.cm.getId
         *
         * @param {JQuery} element
         * @returns {Integer}
         */
        var getModuleId = function(element) {
            var id;
            Y.use('moodle-course-util', function(Y) {
                id = Y.Moodle.core_course.util.cm.getId(Y.Node(element.get(0)));
            });
            return id;
        };

        /**
         * Wrapper for Y.Moodle.core_course.util.cm.getName
         *
         * @param {JQuery} element
         * @returns {String}
         */
        var getModuleName = function(element) {
            var name;
            Y.use('moodle-course-util', function(Y) {
                name = Y.Moodle.core_course.util.cm.getName(Y.Node(element.get(0)));
            });
            return name;
        };

        /**
         * Wrapper for M.util.add_spinner for an activity
         *
         * @param {JQuery} activity
         * @returns {Node}
         */
        var addActivitySpinner = function(activity) {
            activity.addClass(CSS.EDITINPROGRESS);
            var actionarea = activity.find(SELECTOR.ACTIONAREA).get(0);
            if (actionarea) {
                var spinner = M.util.add_spinner(Y, Y.Node(actionarea));
                spinner.show();
                return spinner;
            }
            return null;
        };

        /**
         * Wrapper for M.util.add_spinner for a section
         *
         * @param {JQuery} sectionelement
         * @returns {Node}
         */
        var addSectionSpinner = function(sectionelement) {
            sectionelement.addClass(CSS.EDITINPROGRESS);
            var actionarea = sectionelement.find(SELECTOR.SECTIONACTIONMENU).get(0);
            if (actionarea) {
                var spinner = M.util.add_spinner(Y, Y.Node(actionarea));
                spinner.show();
                return spinner;
            }
            return null;
        };

        /**
         * Wrapper for M.util.add_lightbox
         *
         * @param {JQuery} sectionelement
         * @returns {Node}
         */
        var addSectionLightbox = function(sectionelement) {
            var lightbox = M.util.add_lightbox(Y, Y.Node(sectionelement.get(0)));
            lightbox.show();
            return lightbox;
        };

        /**
         * Removes the spinner element
         *
         * @param {JQuery} element
         * @param {Node} spinner
         * @param {Number} delay
         */
        var removeSpinner = function(element, spinner, delay) {
            window.setTimeout(function() {
                element.removeClass(CSS.EDITINPROGRESS);
                if (spinner) {
                    spinner.hide();
                }
            }, delay);
        };

        /**
         * Removes the lightbox element
         *
         * @param {Node} lightbox lighbox YUI element returned by addSectionLightbox
         * @param {Number} delay
         */
        var removeLightbox = function(lightbox, delay) {
            if (lightbox) {
                window.setTimeout(function() {
                    lightbox.hide();
                }, delay);
            }
        };

        /**
         * Sets up action menu for the element (section or module)
         *
         * @param {String} elementid CSS id attribute of the element
         * @param {Boolean} openmenu whether to open menu - this can be used when re-initiating menu after indent action was pressed
         */
        var initActionMenu = function(elementid, openmenu) {
            // Initialise action menu in the new activity.
            Y.use('moodle-course-coursebase', function() {
                M.course.coursebase.invoke_function('setup_for_resource', '#' + elementid);
            });
            if (M.core.actionmenu && M.core.actionmenu.newDOMNode) {
                M.core.actionmenu.newDOMNode(Y.one('#' + elementid));
            }
            // Open action menu if the original element had data-keepopen.
            if (openmenu) {
                var locator = SELECTOR.MENU + ' ' + SELECTOR.TOGGLE;
                Y.one('#' + elementid + ' ' + locator).simulate('click');
            }
        };

        /**
         * Returns focus to the element that was clicked or "Edit" link if element is no longer visible.
         *
         * @param {String} elementId CSS id attribute of the element
         * @param {String} action data-action property of the element that was clicked
         */
        var focusActionItem = function(elementId, action) {
            var mainelement = $('#' + elementId);
            var selector = '[data-action=' + action + ']';
            if (action === 'groupsseparate' || action === 'groupsvisible' || action === 'groupsnone') {
                // New element will have different data-action.
                selector = '[data-action=groupsseparate],[data-action=groupsvisible],[data-action=groupsnone]';
            }
            if (mainelement.find(selector).is(':visible')) {
                mainelement.find(selector).focus();
            } else {
                // Element not visible, focus the "Edit" link.
                mainelement.find(SELECTOR.MENU + ' ' + SELECTOR.TOGGLE).focus();
            }
        };

        /**
         * Performs an action on a module (moving, deleting, duplicating, hiding, etc.)
         *
         * @param {JQuery} mainelement activity element we perform action on
         * @param {Nunmber} cmid
         * @param {JQuery} target the element (menu item) that was clicked
         */
        var editModule = function(mainelement, cmid, target) {
            var keepopen = target.attr('data-keepopen'),
                    action = target.attr('data-action');
            var spinner = addActivitySpinner(mainelement);
            var promises = ajax.call([{
                methodname: 'core_course_module_action',
                args: {id: cmid,
                    action: action,
                    sr: target.attr('data-sr') ? target.attr('data-sr') : 0
                }
            }], true);

            var lightbox;
            if (action === 'duplicate') {
                lightbox = addSectionLightbox(target.closest(SELECTOR.SECTIONLI));
            }
            $.when.apply($, promises)
                .done(function(data) {
                    mainelement.replaceWith(data);
                    // Initialise action menu for activity(ies) added as a result of this.
                    $('<div>' + data + '</div>').find(SELECTOR.ACTIVITYLI).each(function(index) {
                        initActionMenu($(this).attr('id'), keepopen);
                        if (index === 0) {
                            focusActionItem($(this).attr('id'), action);
                        }
                    });
                    // Remove spinner and lightbox with a delay.
                    removeSpinner(mainelement, spinner, 400);
                    removeLightbox(lightbox, 400);
                    // Trigger event that can be observed by course formats.
                    mainelement.trigger({type: 'coursemoduleedited', ajaxreturn: data, action: action});
                }).fail(function(ex) {
                    // Remove spinner and lightbox.
                    removeSpinner(mainelement, spinner);
                    removeLightbox(lightbox);
                    // Trigger event that can be observed by course formats.
                    var e = $.Event('coursemoduleeditfailed', {exception: ex, action: action});
                    mainelement.trigger(e);
                    if (!e.isDefaultPrevented()) {
                        notification.exception(ex);
                    }
                });
        };

        /**
         * Displays the delete confirmation and deletes a module
         *
         * @param {JQuery} mainelement activity element we perform action on
         * @param {function} onconfirm function to execute on confirm
         */
        var confirmDeleteModule = function(mainelement, onconfirm) {
            var modtypename = mainelement.attr('class').match(/modtype_([^\s]*)/)[1];
            var modulename = getModuleName(mainelement);

            str.get_string('pluginname', modtypename).done(function(pluginname) {
                var plugindata = {
                    type: pluginname,
                    name: modulename
                };
                str.get_strings([
                    {key: 'confirm'},
                    {key: modulename === null ? 'deletechecktype' : 'deletechecktypename', param: plugindata},
                    {key: 'yes'},
                    {key: 'no'}
                ]).done(function(s) {
                        notification.confirm(s[0], s[1], s[2], s[3], onconfirm);
                    }
                );
            });
        };

        /**
         * Replaces a section action menu item with another one (for example Show->Hide)
         *
         * @param {JQuery} sectionelement
         * @param {String} selector
         * @param {String} image image name ("i/show", "i/hide", etc.)
         * @param {String} stringname
         * @param {String} stringcomponent
         * @param {String} titlestr
         * @param {String} titlecomponent
         */
        var replaceSectionActionItem = function(sectionelement, selector, image, stringname,
                                                   stringcomponent, titlestr, titlecomponent) {
            var actionitem = sectionelement.find(SELECTOR.SECTIONACTIONMENU + ' ' + selector);
            actionitem.find('img').attr('src', url.imageUrl(image, 'core'));
            str.get_string(stringname, stringcomponent).done(function(newstring) {
                actionitem.find('span.menu-action-text').html(newstring);
                actionitem.attr('title', newstring);
            });
            if (titlestr) {
                str.get_string(titlestr, titlecomponent).done(function(newtitle) {
                    actionitem.attr('title', newtitle);
                });
            }
        };

        /**
         * Toggles the section visibility, requests and re-renders all activities inside it
         *
         * @param {String} courseformat name of the current course format (for fetching strings)
         * @param {JQuery} sectionelement section element
         * @param {Number} sectionid id of the section in moodle database
         * @param {Number} sr section to return to (used for building links)
         */
        var changeSectionVisibility = function(courseformat, sectionelement, sectionid, sr) {
            var spinner = addSectionSpinner(sectionelement);
            var lightbox = addSectionLightbox(sectionelement);
            var action = sectionelement.hasClass('hidden') ? 'show' : 'hide';
            var ajaxargs = [{
                methodname: 'core_course_edit_section',
                args: {id: sectionid, action: action}
            }];
            // For each activity in this section request the updated html (with proper visibility status and action menu).
            Y.use('moodle-course-util', function() {
                sectionelement.find(SELECTOR.ACTIVITYLI).each(function() {
                    ajaxargs.push({
                        methodname: 'core_course_module_action',
                        args: {id: getModuleId($(this)), action: 'view', sr: sr ? sr : 0}
                    });
                });
            });
            var promises = ajax.call(ajaxargs, true);

            $.when.apply($, promises)
                .done(function() {
                    if (action === 'hide') {
                        sectionelement.addClass('hidden');
                        replaceSectionActionItem(sectionelement, SELECTOR.SHOWHIDE, 'i/show',
                            'showfromothers', 'format_' + courseformat);
                    } else {
                        sectionelement.removeClass('hidden');
                        replaceSectionActionItem(sectionelement, SELECTOR.SHOWHIDE, 'i/hide',
                            'hidefromothers', 'format_' + courseformat);
                    }

                    // Responses with indexes 1 and above return new HTML for the activity nodes. Update them.
                    var replaceActivityHtmlWith = function(activityhtml) {
                        $('<div>' + activityhtml + '</div>').find(SELECTOR.ACTIVITYLI).each(function() {
                            var id = $(this).attr('id');
                            $('#' + id).replaceWith(activityhtml);
                            initActionMenu(id, false);
                        });
                    };
                    for (var i = 1; i < arguments.length; i++) {
                        replaceActivityHtmlWith(arguments[i]);
                    }

                    // Remove spinner and lightbox from the section.
                    removeSpinner(sectionelement, spinner, 400);
                    removeLightbox(lightbox, 400);
                    // Trigger event that can be observed by course formats.
                    sectionelement.trigger({type: 'coursesectionedited', ajaxreturn: arguments, action: action});
                }).fail(function(ex) {
                    removeSpinner(sectionelement, spinner);
                    removeLightbox(lightbox);
                    // Trigger event that can be observed by course formats.
                    var e = $.Event('coursesectioneditfailed', {exception: ex, action: action});
                    sectionelement.trigger(e);
                    if (!e.isDefaultPrevented()) {
                        notification.exception(ex);
                    }
            });

        };

        /**
         * Toggles section highlighting
         *
         * @param {String} courseformat name of the current course format (for fetching strings)
         * @param {JQuery} sectionelement section element
         * @param {Number} sectionid id of the section in moodle database
         */
        var toggleSectionHighlight = function(courseformat, sectionelement, sectionid) {
            var spinner = addSectionSpinner(sectionelement);
            var lightbox = addSectionLightbox(sectionelement);
            var action = sectionelement.hasClass('current') ? 'removemarker' : 'setmarker';
            var ajaxargs = [{
                methodname: 'core_course_edit_section',
                args: {id: sectionid, action: action}
            }];
            var oldmarker = (action === 'setmarker') ? $(SELECTOR.SECTIONLI + '.current') : null;
            var promises = ajax.call(ajaxargs, true);

            $.when.apply($, promises)
                .done(function(data1) {
                    if (action === 'setmarker') {
                        sectionelement.addClass('current');
                        replaceSectionActionItem(sectionelement, SELECTOR.HIGHLIGHT, 'i/marked',
                            'highlightoff', 'core', 'markedthistopic', 'core');
                        oldmarker.removeClass('current');
                        replaceSectionActionItem(oldmarker, SELECTOR.HIGHLIGHT, 'i/marker',
                            'highlight', 'core', 'markthistopic', 'core');
                    } else {
                        sectionelement.removeClass('current');
                        replaceSectionActionItem(sectionelement, SELECTOR.HIGHLIGHT, 'i/marker',
                            'highlight', 'core', 'markthistopic', 'core');
                    }
                    removeSpinner(sectionelement, spinner, 400);
                    removeLightbox(lightbox, 400);
                    // Trigger event that can be observed by course formats.
                    sectionelement.trigger({type: 'coursesectionedited', ajaxreturn: data1, action: action,
                        oldmarker: oldmarker});
                }).fail(function(ex) {
                    removeSpinner(sectionelement, spinner);
                    removeLightbox(sectionelement);
                    // Trigger event that can be observed by course formats.
                    var e = $.Event('coursesectioneditfailed', {exception: ex, action: action});
                    sectionelement.trigger(e);
                    if (!e.isDefaultPrevented()) {
                        notification.exception(ex);
                    }
            });

        };

        Y.use('moodle-course-coursebase', function() {
            // Register a function to be executed after D&D of an activity.
            M.course.coursebase.register_module({
                // eslint-disable-next-line camelcase
                set_visibility_resource_ui: function(args) {
                    var mainelement = $(args.element.getDOMNode());
                    var cmid = getModuleId(mainelement);
                    if (cmid) {
                        var sr = mainelement.find('.' + CSS.EDITINGMOVE).attr('data-sr');
                        var target = $('<span>').attr('data-action', 'view').attr('data-sr', sr);
                        editModule(mainelement, cmid, target);
                    }
                }
            });
        });

        return /** @alias module:core/course */ {

            /**
             * Initialises course page
             *
             * @method init
             * @param {String} courseformat name of the current course format (for fetching strings)
             */
            initCoursePage: function(courseformat) {

                // Add a handler for course module actions.
                $('body').on('click keypress', SELECTOR.ACTIVITYLI + ' ' + SELECTOR.ACTIVITYACTION, function(e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var target = $(this),
                        mainelement = target.closest(SELECTOR.ACTIVITYLI),
                        action = target.attr('data-action'),
                        cmid = getModuleId(mainelement);
                    switch (action) {
                        case 'moveleft':
                        case 'moveright':
                        case 'delete':
                        case 'duplicate':
                        case 'hide':
                        case 'stealth':
                        case 'show':
                        case 'groupsseparate':
                        case 'groupsvisible':
                        case 'groupsnone':
                            break;
                        default:
                            // Nothing to do here!
                            return;
                    }
                    if (!cmid) {
                        return;
                    }
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    if (action === 'delete') {
                        // Deleting requires confirmation.
                        confirmDeleteModule(mainelement, function() {
                            editModule(mainelement, cmid, target);
                        });
                    } else {
                        editModule(mainelement, cmid, target);
                    }
                });

                // Add a handler for section show/hide actions.
                $('body').on('click keypress', SELECTOR.SECTIONLI + ' ' + SELECTOR.SECTIONACTIONMENU + ' ' +
                            SELECTOR.SHOWHIDE, function(e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var target = $(this),
                        sectionelement = target.closest(SELECTOR.SECTIONLI),
                        actionmenu = target.closest(SELECTOR.SECTIONACTIONMENU),
                        sectionid = actionmenu.attr('data-sectionid');
                    if (sectionid) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        changeSectionVisibility(courseformat, sectionelement, sectionid, target.attr('data-sr'));
                    }
                });

                // Add a handler for section highlight actions.
                $('body').on('click keypress', SELECTOR.SECTIONLI + ' ' + SELECTOR.SECTIONACTIONMENU + ' ' +
                            SELECTOR.HIGHLIGHT, function(e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var target = $(this),
                        sectionelement = target.closest(SELECTOR.SECTIONLI),
                        actionmenu = target.closest(SELECTOR.SECTIONACTIONMENU),
                        sectionid = actionmenu.attr('data-sectionid');
                    if (sectionid) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        toggleSectionHighlight(courseformat, sectionelement, sectionid);
                    }
                });
            }
        };
    });