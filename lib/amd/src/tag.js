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
 * AJAX helper for the tag management page.
 *
 * @module     core/tag
 * @package    core_tag
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.0
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/config'],
        function($, ajax, templates, notification, str, cfg) {
    return /** @alias module:core/tag */ {

        /**
         * Initialises handlers for AJAX methods.
         *
         * @method init
         */
        init: function() {
            // Click handler for changing tag type.
            $('body').delegate('.tagflag', 'click', function(e) {
                e.preventDefault();
                var target = $( this ),
                    currentvalue = target.attr('data-value'),
                    flag = (currentvalue === "0") ? 1 : 0,
                    data = {id:1, 
                        flag:flag, 
                        changeflagurl:cfg.wwwroot+'/tag/test.php'};
                    templates.render('core_tag/tagflag', data).done(function(html) {
                        target.replaceWith(html);
                    });
            });
        }
    };
});