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
 * A javascript module to handle list items drag and drop
 *
 * @module     core/sortable_list
 * @class      sortable_list
 * @package    core
 * @copyright  2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log', 'core/drag'], function($, log, drag) {
    var defaultParameters = {
        listSelector: null, /* CSS selector for sortable lists, must be specified during initialization. */
        moveHandlerSelector: null, /* CSS selector for a drag handle. By default the whole item is a handle. */
        isDraggedClass: 'sortable-list-is-dragged', /* Class added to the element that is dragged. */
        currentPositionClass: 'sortable-list-current-position', /* Class added to the current position of a dragged element. */
        sourceListClass: 'sortable-list-source', /* Class added to the list where dragging was started from. */
        targetListClass: 'sortable-list-target', /* Class added to all lists where item can be dropped. */
        onDrop: null, /* Callback to be executed when list item is dropped. */
        onDragStart: null, /* Callback to be executed when dragging of an item started. */
        onMove: null, /* Callback to be executed when item changed current position in the list. */
        onDragCancel: null /* Callback to be executed when dragging was cancelled (Esc pressed while dragging). */
    };

    /**
     * Stores parameters of the currently dragged item
     *
     * @type {Object}
     */
    var params = {};

    /**
     * Stores information about currently dragged item
     *
     * @type {Object}
     */
    var info = {};

    /**
     * Resets the temporary classes assigned during dragging
     */
    var resetDraggedClasses = function() {
        var lists = $(params.listSelector);
        lists.children()
            .removeClass(params.isDraggedClass)
            .removeClass(params.currentPositionClass);
        lists
            .removeClass(params.targetListClass)
            .removeClass(params.sourceListClass);
    };

    /**
     * Handler from dragstart event
     *
     * @param {Event} evt
     */
    var dragstartHandler = function(evt) {
        params = evt.data.params;
        resetDraggedClasses();

        var details = drag.prepare(evt);
        if (!details.start) {
            return;
        }

        // Check that this element belongs to any of registered sortable lists.
        var movedElement = $(evt.currentTarget);

        // Check that we grabbed the element by the handle.
        var moveHandlerSelector = params.moveHandlerSelector;
        if (moveHandlerSelector !== null) {
            // Dragstart event only returns [draggable] element as target. We want to check if the handler
            // was dragged. The target of the last mousedown event is remembered in mouseTarget.
            if (!$(evt.target).closest(moveHandlerSelector, movedElement).length) {
                return;
            }
        }

        // Information about moved element with original location.
        info = {
            draggedElement: movedElement,
            sourceNextElement: movedElement.next(),
            sourceList: movedElement.parent(),
            targetNextElement: movedElement.next(),
            targetList: movedElement.parent(),
            dropped: false,
            startX: details.x,
            startY: details.y,
            startTime: new Date().getTime()
        };

        $(params.listSelector).addClass(params.targetListClass);
        var offset = movedElement.offset(),
            proxy = movedElement.clone();
        movedElement.parent().append(proxy);
        proxy.removeAttr('id').addClass(params.isDraggedClass).css({position: 'fixed'});
        movedElement.addClass(params.currentPositionClass);

        // Start drag.
        proxy.offset(offset);
        drag.start(evt, proxy, dragHandler, dropHandler, dragcancelHandler);
    };

    /**
     *
     * @param {Number} pageX
     * @param {Number} pageY
     * @param {Node} node
     * @param {Number} offset
     * @returns {Object}|null
     */
    var getPositionInNode = function(pageX, pageY, node, offset) {
        if (typeof offset === 'undefined') {
            offset = 0;
        }
        var rect = node.getBoundingClientRect(),
            y = pageY - (rect.top + window.scrollY),
            x = pageX - (rect.left + window.scrollX);
        if (x >= -offset && x <= rect.width + offset && y >= -offset && y <= rect.height + offset) {
            return {
                x: x,
                y: y,
                xRatio: x / Math.max(rect.width, 1),
                yRatio: y / Math.max(rect.height, 1)
            };
        }
        return null;
    };

    /**
     * Called when item is being dragged
     *
     * @param {Number} pageX
     * @param {Number} pageY
     * @param {jQuery} proxy
     * @return {jQuery}
     */
    var dragHandler = function(pageX, pageY, proxy) {
        var found = false,
            isNotProxy = function() {
                return this !== proxy[0];
            };
        if (getPositionInNode(pageX, pageY, info.draggedElement[0]) !== null) {
            // Mouse is over the current position of the dragged element.
            return;
        }
        $(params.listSelector).children().filter(isNotProxy).each(function () {
            var coordinates = getPositionInNode(pageX, pageY, this);
            if (coordinates !== null) {
                // Within the borders!
                if (this === info.draggedElement) {
                    // over the current position - do nothing
                } else if (coordinates.yRatio > 0.5) {
                    // insert after this element
                    moveDraggedElement($(this).parent(), $(this).next().filter(isNotProxy), proxy);
                } else {
                    // insert before this element
                    moveDraggedElement($(this).parent(), $(this), proxy);
                }
                found = true;
                return false;
            }
        });
        if (!found) {
            $(params.listSelector).each(function() {
                if (!$(this).children().filter(isNotProxy).length) {
                    var coordinates = getPositionInNode(pageX, pageY, this, 5);
                    if (coordinates !== null) {
                        moveDraggedElement($(this), $(), proxy);
                    }
                }
            });
        }
    };

    /**
     * Moves the current position of the dragged element
     *
     * @param {jQuery} parentElement
     * @param {jQuery} beforeElement
     */
    var moveDraggedElement = function(parentElement, beforeElement, proxy) {
        var dragEl = info.draggedElement;
        if (beforeElement.length && beforeElement[0] === dragEl[0]) {
            // Insert before the current position - nothing to do.
            return;
        }
        if (parentElement[0] === info.targetList[0] &&
                beforeElement.length === info.targetNextElement.length &&
                beforeElement[0] === info.targetNextElement[0]) {
            // Insert in the same location as the current position - nothing to do.
            return;
        }
        if (beforeElement.length) {
            parentElement[0].insertBefore(dragEl[0], beforeElement[0]);
        } else if (proxy.parent().length && proxy.parent()[0] === parentElement[0]) {
            // Always leave the proxy in the end of the list.
            parentElement[0].insertBefore(dragEl[0], proxy[0]);
        } else {
            parentElement[0].appendChild(dragEl[0]);
        }
        info.targetList = parentElement;
        info.targetNextElement = beforeElement;
        executeCallback('onMove');
    };

    /**
     * Handler for drop event
     *
     * @param {Number} pageX
     * @param {Number} pageY
     * @param {jQuery} proxy
     */
    var dropHandler = function(pageX, pageY, proxy) {
        dragHandler(pageX, pageY, proxy);
        info.endX = pageX;
        info.endY = pageY;
        info.endTime = new Date().getTime();
        executeCallback('onDrop');
        resetDraggedClasses();
    };

    /**
     * Executes callback specified in sortable list parameters
     *
     * @param {String} callbackName
     */
    var executeCallback = function(callbackName) {
        var callback = params[callbackName];
        if (callback !== null) {
            callback(info);
        }
    };

    /**
     * Handler for drag cancel event. Clear the classes and listeners
     */
    var dragcancelHandler = function(pageX, pageY, proxy) {
        // Dragging was cancelled. Return item to the original position.
        moveDraggedElement(info.sourceList, info.sourceNextElement, proxy);
        executeCallback('onDragCancel');
        resetDraggedClasses();
    };

    return {
        /**
         * Initialise sortable list.
         *
         * @param {Object} params Parameters for the list. See defaultParameters above for examples.
         */
        init: function(params) {
            if (typeof params.listSelector === 'undefined') {
                log.error('Parameter listSelector must be specified');
                return;
            }
            params = $.extend({}, defaultParameters, params);
            $(params.listSelector).on('mousedown touchstart', '> *', {params: params}, dragstartHandler);
        }
    };
});
