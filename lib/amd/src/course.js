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
 * TODO
 *
 * @module     core/course
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/url', 'core/log', 'core/yui'],
    function($, ajax, templates, notification, str, url, l, Y) {
        var CSS = {
            EDITINPROGRESS: 'editinprogress',
            SECTIONDRAGGABLE: 'sectiondraggable',
            EDITINGMOVE: 'editing_move'
        };
        var SELECTOR = {
            ACTIVITYLI : 'li.activity',
            ACTIONAREA: '.actions',
            ACTIVITYACTION : 'a.cm-edit-action[data-action]',
            MENU : '.moodle-actionmenu[data-enhance=moodle-core-actionmenu]',
            TOGGLE : '.toggle-display',
            SECTIONLI : 'li.section', // TODO: M.course.format.get_section_selector()
            SECTIONACTIONMENU : '.section_action_menu',
            HIGHLIGHT : 'a.editing_highlight',
            SHOWHIDE : 'a.editing_showhide'
        };

        var get_module_id = function(element) {
            var id;
            Y.use('moodle-course-util', function(Y) {
                id = Y.Moodle.core_course.util.cm.getId(Y.Node(element.get(0)));
            });
            return id;
        };

        var add_activity_spinner = function(activity) {
            activity.addClass(CSS.EDITINPROGRESS);
            var actionarea = activity.find(SELECTOR.ACTIONAREA).get(0);
            if (actionarea) {
                var spinner = M.util.add_spinner(Y, Y.Node(actionarea));
                spinner.show();
                return spinner;
            }
            return null;
        };

        var add_section_spinner = function(sectionelement) {
            sectionelement.addClass(CSS.EDITINPROGRESS);
            var actionarea = sectionelement.find(SELECTOR.SECTIONACTIONMENU).get(0);
            if (actionarea) {
                var spinner = M.util.add_spinner(Y, Y.Node(actionarea));
                spinner.show();
                return spinner;
            }
            return null;
        };

        var remove_spinner = function(element, spinner, delay) {
            window.setTimeout(function() {
                element.removeClass(CSS.EDITINPROGRESS);
                if (spinner) {
                    spinner.hide();
                }
            }, delay);
        };

        var init_action_menu = function(elementid, openmenu) {
            // Initialise action menu in the new activity.
            Y.use('moodle-course-coursebase', function(Y) {
                M.course.coursebase.invoke_function('setup_for_resource', '#'+elementid);
            });
            if (M.core.actionmenu && M.core.actionmenu.newDOMNode) {
                M.core.actionmenu.newDOMNode(Y.one('#'+elementid));
            }
            // Open action menu if the original element had data-keepopen.
            if (openmenu) {
                var locator = SELECTOR.MENU + ' ' + SELECTOR.TOGGLE;
                Y.one('#'+elementid+' '+locator).simulate('click');
            }
        };

        var edit_module = function(mainelement, cmid, target) {
            var keepopen = target.attr('data-keepopen'),
                    action = target.attr('data-action');
            var spinner = add_activity_spinner(mainelement);
            var promises = ajax.call([{
                methodname: 'core_course_edit_course_module',
                args: { id : cmid,
                    action : action,
                    sr : target.attr('data-sr')
                }
            }], true);

            $.when.apply($, promises)
                .done( function(data) {
                    mainelement.replaceWith(data);
                    // Initialise action menu for activity(ies) added as a result of this.
                    $('<div>'+data+'</div>').find(SELECTOR.ACTIVITYLI).each(function() {
                        init_action_menu($(this).attr('id'), keepopen);
                    });
                    // Remove spinner with a delay.
                    remove_spinner(mainelement, spinner, 400);
                    // Trigger event that can be observed by course formats.
                    mainelement.trigger({type: 'coursemoduleedited', ajaxreturn: data, action: action});
                }).fail(function(ex) {
                    // Remove spinner.
                    remove_spinner(mainelement, spinner);
                    // Trigger event that can be observed by course formats.
                    var e = $.Event('coursemoduleeditfailed', { exception: ex, action: action });
                    mainelement.trigger(e);
                    if (!e.isDefaultPrevented()) {
                        notification.exception(ex);
                    }
                });
        };


        var replace_section_action_item = function(sectionli, selector, image, stringname, stringcomponent, titlestr, titlecomponent) {
            var actionitem = sectionli.find(SELECTOR.SECTIONACTIONMENU + ' ' + selector);
            actionitem.find('img').attr('src', url.imageUrl(image, 'core'));
            str.get_string(stringname, stringcomponent).done(function(newstring) {
                actionitem.find('span.menu-action-text').html(newstring);
                actionitem.attr('title', newstring);
            });
            if (titlestr) {
                str.get_string(titlestr, titlecomponent).done(function (newtitle) {
                    actionitem.attr('title', newtitle);
                });
            }
        };

        var change_section_visibility = function(courseformat, mainelement, actionmenu, sectionid, sr) {
            var spinner = add_section_spinner(mainelement);
            var action = mainelement.hasClass('hidden') ? 'show' : 'hide';
            var ajaxargs = [{
                methodname: 'core_course_edit_section',
                args: { id : sectionid, action : action }
            }];
            // For each activity in this section request the updated html (with proper visibility status and action menu).
            Y.use('moodle-course-util', function(Y) {
                mainelement.find(SELECTOR.ACTIVITYLI).each(function() {
                    ajaxargs.push({
                        methodname: 'core_course_edit_course_module',
                        args: { id : get_module_id($(this)), action : 'view', sr : sr }
                    });
                });
            });
            var promises = ajax.call(ajaxargs, true);

            $.when.apply($, promises)
                .done( function() {
                    if (action === 'hide') {
                        mainelement.addClass('hidden');
                        replace_section_action_item(mainelement, SELECTOR.SHOWHIDE, 'i/show', 'showfromothers', 'format_'+courseformat);
                    } else {
                        mainelement.removeClass('hidden');
                        replace_section_action_item(mainelement, SELECTOR.SHOWHIDE, 'i/hide', 'hidefromothers', 'format_'+courseformat);
                    }
                    for (var i=1; i<arguments.length; i++) {
                        var data = arguments[i];
                        // Update activities html inside this section.
                        $('<div>'+data+'</div>').find(SELECTOR.ACTIVITYLI).each(function() {
                            var id = $(this).attr('id');
                            $('#'+id).replaceWith(data);
                            init_action_menu(id, false);
                        });
                    }
                    remove_spinner(mainelement, spinner, 400);
                    // Trigger event that can be observed by course formats.
                    mainelement.trigger({type: 'coursesectionedited', ajaxreturn: arguments, action: action});
                }).fail(function(ex) {
                    remove_spinner(mainelement, spinner);
                    // Trigger event that can be observed by course formats.
                    var e = $.Event('coursesectioneditfailed', { exception: ex, action: action });
                    mainelement.trigger(e);
                    if (!e.isDefaultPrevented()) {
                        notification.exception(ex);
                    }
            });

        };

        var toggle_section_highlight = function(courseformat, mainelement, actionmenu, sectionid) {
            var spinner = add_section_spinner(mainelement);
            var action = mainelement.hasClass('current') ? 'removemarker' : 'setmarker';
            var ajaxargs = [{
                methodname: 'core_course_edit_section',
                args: { id : sectionid, action : action }
            }];
            var oldmarker = (action === 'setmarker') ? $(SELECTOR.SECTIONLI + '.current') : null;
            var promises = ajax.call(ajaxargs, true);

            $.when.apply($, promises)
                .done( function(data1) {
                    if (action === 'setmarker') {
                        mainelement.addClass('current');
                        replace_section_action_item(mainelement, SELECTOR.HIGHLIGHT, 'i/marked',
                            'highlightoff', 'core', 'markedthistopic', 'core');
                        oldmarker.removeClass('current');
                        replace_section_action_item(oldmarker, SELECTOR.HIGHLIGHT, 'i/marker',
                            'highlight', 'core', 'markthistopic', 'core');
                    } else {
                        mainelement.removeClass('current');
                        replace_section_action_item(mainelement, SELECTOR.HIGHLIGHT, 'i/marker',
                            'highlight', 'core', 'markthistopic', 'core');
                    }
                    remove_spinner(mainelement, spinner, 400);
                    // Trigger event that can be observed by course formats.
                    mainelement.trigger({type: 'coursesectionedited', ajaxreturn: data1, action: action,
                        oldmarker: oldmarker});
                }).fail(function(ex) {
                    remove_spinner(mainelement, spinner);
                    // Trigger event that can be observed by course formats.
                    var e = $.Event('coursesectioneditfailed', { exception: ex, action: action });
                    mainelement.trigger(e);
                    if (!e.isDefaultPrevented()) {
                        notification.exception(ex);
                    }
            });

        };

        Y.use('moodle-course-coursebase', function(Y) {
            // Register a function to be executed after D&D of an activity.
            M.course.coursebase.register_module({
                set_visibility_resource_ui: function(args) {
                    var mainelement = $(args.element.getDOMNode());
                    var cmid = get_module_id(mainelement);
                    if (cmid) {
                        var sr = mainelement.find('.'+CSS.EDITINGMOVE).attr('data-sr');
                        var target = $('<span>').attr('data-action', 'view').attr('data-sr', sr);
                        edit_module(mainelement, cmid, target);
                    }
                }
            });
        });

        return /** @alias module:core/course */ {

            /**
             * Initialises course page
             *
             * @method init
             */
            init_course_page: function(courseformat) {

                // Add a handler for course module actions.
                $('body').on('click keypress', SELECTOR.ACTIVITYLI + ' ' + SELECTOR.ACTIVITYACTION, function (e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var target = $(this),
                        mainelement = target.closest(SELECTOR.ACTIVITYLI),
                        action = target.attr('data-action'),
                        cmid = get_module_id(mainelement);
                    switch (action) {
                        case 'moveleft':
                        case 'moveright':
                        case 'delete':
                        case 'duplicate':
                        case 'hide':
                        case 'hideoncoursepage':
                        case 'show':
                        case 'groupsseparate':
                        case 'groupsvisible':
                        case 'groupsnone':
                            break;
                        default:
                            // Nothing to do here!
                            return;
                    }
                    if (cmid) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        edit_module(mainelement, cmid, target);
                    }
                });

                // Add a handler for section show/hide actions.
                $('body').on('click keypress', SELECTOR.SECTIONLI + ' ' + SELECTOR.SECTIONACTIONMENU + ' ' + SELECTOR.SHOWHIDE, function (e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var target = $(this),
                        mainelement = target.closest(SELECTOR.SECTIONLI),
                        actionmenu = target.closest(SELECTOR.SECTIONACTIONMENU),
                        sectionid = actionmenu.attr('data-sectionid');
                    if (sectionid) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        change_section_visibility(courseformat, mainelement, actionmenu, sectionid, target.attr('data-sr'));
                        // TODO too many arguments
                    }
                });

                // Add a handler for section highlight actions.
                $('body').on('click keypress', SELECTOR.SECTIONLI + ' ' + SELECTOR.SECTIONACTIONMENU + ' ' + SELECTOR.HIGHLIGHT, function (e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var target = $(this),
                        mainelement = target.closest(SELECTOR.SECTIONLI),
                        actionmenu = target.closest(SELECTOR.SECTIONACTIONMENU),
                        sectionid = actionmenu.attr('data-sectionid');
                    if (sectionid) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        toggle_section_highlight(courseformat, mainelement, actionmenu, sectionid);
                        // TODO too many arguments
                    }
                });
            }
        };
    });