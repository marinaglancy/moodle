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
 * Presents a modal popup for choosing file group and file types.
 *
 * Note: YUI is used for the dialogue only. When a suitable non-
 * YUI substitute becomes available, it should be trivial to
 * adapt this across.
 *
 * @package   core/form-filetypes
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/yui', 'core/str', 'core/notification', 'core/ajax', 'core/templates'], function($, Y,
    str, notification, ajax, templates) {
    "use strict";

    /**
     * Handle UI changes from toggling a group or filetype checkbox.
     *
     * @method handleFiletypeChange
     * @param {Event} event  a checkbox click event.
     */
    function handleFiletypeChange(event) {
        if (event.target.value.indexOf('.') === 0) {
            // Single type checkbox toggled, so update any other instances having
            // the same value to be the same state.
            $(event.data).find('input[type=checkbox]')
                .filter(function(index, element) {
                    return element.value === event.target.value;
                })
                .prop('checked', event.target.checked);
        } else {
            // Group checkbox (i.e. non-mimetype) toggled, so update the group
            // container to hide the types within.
            var typegroup = $(event.target).closest('.filetypegroup', event.data);
            typegroup.toggleClass('selected', event.target.checked);
            if (typegroup.attr('data-groupkey') === '*') {
                // "All file types" was checked/unchecked - toggle the class for the whole selector.
                var selector = typegroup.closest('.form-filetypes-selector', event.data);
                selector.toggleClass('allselected', event.target.checked);
                if (event.target.checked) {
                    // Uncheck all other typegroups.
                    selector.find('.filetypegroup:not([data-groupkey="*"]) input[type=checkbox]').each(function(index, element) {
                        element.checked = false;
                    });
                }
            } else if (event.target.checked) {
                // A group of filetypes was checked. Uncheck individual types that are inside this group.
                typegroup.find('ul input[type=checkbox]').each(function(index, element) {
                    $(event.data).find('input[type=checkbox][value="' + element.value + '"]')
                        .prop('checked', false);
                });
            }
        }
    }

    /**
     * Refresh the label on the Moodle form describing the chosen file types.
     *
     * @method updateLabel
     * @param {String[]} values      the groups and types to be displayed.
     * @param {JQuery}   labelelem   the static form element receiving the user-friendly types chosen.
     * @param {Object[]} typegroups  type groups.
     * @param {Object[]} alltypes    all file types.
     */
    function updateLabel(values, labelelem, typegroups, alltypes) {
        var context = {items: []},
            item,
            i;

        for (i = 0; i < values.length; i++) {
            item = alltypes[values[i]] || typegroups[values[i]];
            if (item) {
                context.items.push(item);
            }
        }

        templates.render("core/form_filetypes_label", context)
            .done(function(r) {
                labelelem.html(r);
            }).fail(notification.excetion);
    }

    /**
     * Collect the values of the ticked checkboxes in the given container element.
     *
     * @method fetchChoices
     * @param {HTMLElement} container  the HTML element containing the checkboxes.
     * @return {String[]}  the chosen values.
     */
    function fetchChoices(container) {
        var chosen = [];

        $(container).find('input[type=checkbox]')
            .each(function(index, element) {
                if (element.checked && chosen.indexOf(element.value) < 0) {
                    chosen.push(element.value);
                }
            });

        return chosen;
    }

    /**
     * Displays and operates the modal popup for choosing group and file types.
     *
     * @method selectTypes
     * @param {String} title         the title for the window.
     * @param {JQuery} valueelem     the hidden form element containing the control value.
     * @param {JQuery} labelelem     the static form element receiving the user-friendly types chosen.
     * @param {Object[]} typegroups  type groups.
     * @param {Object[]} alltypes    all file types.
     */
    function selectTypes(title, valueelem, labelelem, typegroups, alltypes) {
        var group,
            values = valueelem.attr('value').replace(' ', '').split(','),
            context = {groups: []},
            groupctx,
            promisestrsave,
            promisestrcancel,
            promisetpl;

        /**
         * Adds an item to the types list in groupctx.
         *
         * @param {String} type A mimetype.
         */
        function addType(type) {
            var typectx = {
                typekey: type,
                selected: (values.indexOf(type) >= 0),
                name: alltypes[type].name,
                extlist: alltypes[type].extlist
            };
            groupctx.types.push(typectx);
        }

        for (group in typegroups) {
            if (typegroups[group] === undefined) {
                // An unrecognised group.
                continue;
            } else if (typegroups[group].types.length < 1 && group !== '*') {
                // An empty type group.
                continue;
            }

            groupctx = {
                groupkey: group,
                name: typegroups[group].name,
                extlist: typegroups[group].extlist,
                isoption: (group !== '-' && typegroups[group].isoption),
                selected: (values.indexOf(group) >= 0),
                types: []
            };
            typegroups[group].types.forEach(addType);
            context.groups.push(groupctx);
            if (values.indexOf(group) >= 0 && group === '*') {
                context.allselected = true;
            }
        }

        promisestrsave = str.get_string('savechoices', 'form');
        promisestrcancel = str.get_string('cancel', 'moodle');
        promisetpl = templates.render("core/form_filetypes_selector", context);

        $.when(promisestrsave, promisestrcancel, promisetpl).then(
            // Done callback.
            function(strsave, strcancel, template) {
                var bodynode;

                Y.use('moodle-core-notification-dialogue', function() {
                    var dialogue = new M.core.dialogue({
                        bodyContent: template[0],
                        headerContent: title,
                        draggable: true,
                        modal: true,
                        extraClasses: ['form-filetypes-chooser']
                    });

                    bodynode = dialogue.bodyNode.getDOMNode();
                    dialogue.addButton({
                        name: 'save',
                        label: strsave,
                        action: function() {
                            var chosen = fetchChoices(bodynode);
                            updateLabel(chosen, labelelem, typegroups, alltypes);
                            valueelem.attr('value', chosen.join(','));
                            dialogue.hide();
                        },
                        section: Y.WidgetStdMod.FOOTER
                    });
                    dialogue.addButton({
                        label: strcancel,
                        action: function(e) {
                            e.preventDefault();
                            dialogue.hide();
                        },
                        section: Y.WidgetStdMod.FOOTER
                    });
                    dialogue.show();
                    dialogue.after('visibleChange', function() {
                        dialogue.destroy(true);
                    });
                });

                $(bodynode).on('change', 'input[type=checkbox]', bodynode, handleFiletypeChange);
            },

            // Fail callback.
            notification.exception
        );
    }

    return /** @alias module:core/form-filetypes */ {
        /**
         * Initialise the filetype chooser.
         *
         * @method initialise
         * @param {String} id         the hidden form element containing the control value.
         * @param {String} title      the title for the modal popup.
         * @param {String} filetypes  the list of mimetypes and type groups to limit choices to.
         * @param {Boolean} allowall  allow selection of "All file types"
         */
        initialise: function(id, title, filetypes, allowall) {
            var valueelem = $(document.getElementById(id)),
                labelelem = $(document.getElementById(id + '_label')),
                buttonelem = $(document.getElementById(id + '_choose')),
                promisetypes;

            if (valueelem.length != 1 || labelelem.length != 1 || buttonelem.length != 1) {
                return;
            }

            buttonelem.on('click', function() {
                if (promisetypes === undefined) {
                    // Load the type groups and types on first opening of the dialog.
                    promisetypes = $.Deferred();

                    ajax.call([{
                        methodname: 'core_get_form_file_types_and_groups',
                        args: {
                            filetypes: filetypes,
                            allowall: allowall,
                            lang: $('html').attr('lang').replace('-', '_')
                        },
                        done: function(response) {
                            var typegroups = {},
                                alltypes = {};

                            response.typegroups.forEach(function(item) {
                                typegroups[item.id] = item;
                            });
                            response.alltypes.forEach(function(item) {
                                alltypes[item.id] = item;
                            });
                            promisetypes.resolve(typegroups, alltypes);
                        },
                        fail: notification.exception
                    }], true, false);
                }

                promisetypes.done(function(typegroups, alltypes) {
                    selectTypes(title, valueelem, labelelem, typegroups, alltypes);
                });
            });
        }
    };
});
