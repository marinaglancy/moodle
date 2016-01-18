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
 * AJAX helper for the inline editing a value.
 *
 * @module     core/editabletitle
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/config'],
        function($, ajax, templates, notification, str, cfg) {

    $('body').on('click keypress', '[data-editabletitle] [data-editabletitlelink]', function(e) {
        if (e.type === 'keypress' && e.keyCode !== 13) {
            return;
        }
        e.stopImmediatePropagation();
        e.preventDefault();
        var target = $(this),
            mainelement = $( target.closest('[data-editabletitle]').get(0) ),
            inputelement = $( mainelement.find('input').get(0) ),
            identifier = mainelement.attr('data-identifier'),
            callback = mainelement.attr('data-callback');

        var change_name = function(identifier, callback, newname) {
            var promises = ajax.call([{
                methodname: 'core_update_generic_title',
                args: { identifier : identifier ,
                    callback : callback ,
                    value : newname }
            }], true);

            $.when.apply($, promises)
                .done( function(data) {
                    templates.render('core/editabletitle', data).done(function(html, js) {
                        templates.replaceNode(mainelement, html, js);
                        $(mainelement.find('[data-editabletitlelink]').get(0)).focus();
                    });
                }).fail(notification.exception);
        };

        var turn_editing_off = function() {
            $('span.editabletitle.quickeditingon').each(function() {
                var td = $( this ),
                    input = $( td.find('input').get(0) );
                input.off();
                td.removeClass('quickeditingon');
                // Reset input value to the one that was there before editing.
                input.val(td.attr('data-value'));
            });
        };

        // Turn editing on for the current element and register handler for Enter/Esc keys.
        turn_editing_off();
        mainelement.addClass('quickeditingon');
        mainelement.attr('data-value', inputelement.val());
        inputelement.focus();
        inputelement.select();
        inputelement.on('keyup keypress focusout', function(e) {
            if (cfg.behatsiterunning && e.type === 'focusout') {
                // Behat triggers focusout too often.
                return;
            }
            if (e.type === 'keypress' && e.keyCode === 13) {
                // We need 'keypress' event for Enter because keyup/keydown would catch Enter that was pressed in other fields.
                change_name(identifier, callback, inputelement.val());
                turn_editing_off();
            }
            if ((e.type === 'keyup' && e.keyCode === 27) || e.type === 'focusout') {
                // We need 'keyup' event for Escape because keypress does not work with Escape.
                turn_editing_off();
            }
        });
    });

    return {};
});