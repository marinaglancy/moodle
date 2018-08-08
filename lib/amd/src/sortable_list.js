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
 * Example of usage:
 *
 * define(['jquery', 'core/sortable_list'], function($, sortableList) {
 *     sortableList.init({
 *         listSelector: 'ul.my-awesome-list', // mandatory, CSS selector for the list (usually <ul> or <tbody>)
 *         moveHandlerSelector: '.draghandle'  // optional but recommended, CSS selector of the crossarrow handle
 *     });
 *     $('ul.my-awesome-list > *').on('sortablelist-drop', function(evt) {
 *         console.log(evt);
 *     });
 * }
 *
 * For the full list of possible parameters see var defaultParameters below.
 *
 * The following jQuery events are fired:
 * - sortablelist-dragstart : when user started dragging a list element
 * - sortablelist-drag : when user dragged a list element to a new position
 * - sortablelist-drop : when user dropped a list element
 * - sortablelist-dragend : when user finished dragging - either fired right after dropping or
 *                          if "Esc" was pressed during dragging
 *
 * @module     core/sortable_list
 * @class      sortable_list
 * @package    core
 * @copyright  2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log', 'core/autoscroll'], function($, log, autoScroll) {
    var defaultParameters = {
        listSelector: null, /* CSS selector for sortable lists, must be specified during initialization. */
        isHorizontal: false, /* Set to true if the list is horizontal. */
        moveHandlerSelector: null, /* CSS selector for a drag handle. By default the whole item is a handle. */
        isDraggedClass: 'sortable-list-is-dragged', /* Class added to the element that is dragged. */
        currentPositionClass: 'sortable-list-current-position', /* Class added to the current position of a dragged element. */
        sourceListClass: 'sortable-list-source', /* Class added to the list where dragging was started from. */
        targetListClass: 'sortable-list-target', /* Class added to all lists where item can be dropped. */
        overElementClass: 'sortable-list-over-element', /* Class added to the list element when the dragged element is above it. */
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
     * Stores the proxy object
     *
     * @type {jQuery}
     */
    var proxy;

    /**
     * Stores initial position of the proxy
     *
     * @type {Object}
     */
    var proxyDelta;

    /**
     * Resets the temporary classes assigned during dragging
     */
    var resetDraggedClasses = function() {
        var lists = $(params.listSelector);
        lists.children()
            .removeClass(params.isDraggedClass)
            .removeClass(params.currentPositionClass)
            .removeClass(params.overElementClass);
        lists
            .removeClass(params.targetListClass)
            .removeClass(params.sourceListClass);
    };

    /**
     * {Event} stores the last event that had pageX and pageY defined
     */
    var lastEvent;

    /**
     * Calculates evt.pageX, evt.pageY, evt.clientX and evt.clientY
     *
     * For touch events pageX and pageY are taken from the first touch;
     * For the emulated mousemove event they are taken from the last real event.
     *
     * @param {Event} evt
     */
    var calculatePositionOnPage = function(evt) {

        if (evt.originalEvent && evt.originalEvent.touches[0] !== undefined) {
            // This is a touchmove or touchstart event, get position from the first touch position.
            var touch = evt.originalEvent.touches[0];
            evt.pageX = touch.pageX;
            evt.pageY = touch.pageY;
        }

        if (evt.pageX === undefined) {
            // Information is not present in case of touchend or when event was emulated by autoScroll.
            // Take the absolute mouse position from the last event.
            evt.pageX = lastEvent.pageX;
            evt.pageY = lastEvent.pageY;
        } else {
            lastEvent = evt;
        }

        if (evt.clientX === undefined) {
            // If not provided in event calculate relative mouse position.
            evt.clientX = Math.round(evt.pageX - $(window).scrollLeft());
            evt.clientY = Math.round(evt.pageY - $(window).scrollTop());
        }
    };

    /**
     * Handler from dragstart event
     *
     * @param {Event} evt
     */
    var dragstartHandler = function(evt) {
        params = evt.data.params;
        resetDraggedClasses();

        calculatePositionOnPage(evt);
        var movedElement = $(evt.currentTarget);

        // Check that we grabbed the element by the handle.
        if (params.moveHandlerSelector !== null) {
            if (!$(evt.target).closest(params.moveHandlerSelector, movedElement).length) {
                return;
            }
        }

        evt.stopPropagation();
        evt.preventDefault();

        // Information about moved element with original location.
        // This object is passed to all registered callbacks (onDrop, onDragStart, onMove, onDragCancel).
        info = {
            draggedElement: movedElement,
            sourceNextElement: movedElement.next(),
            sourceList: movedElement.parent(),
            targetNextElement: movedElement.next(),
            targetList: movedElement.parent(),
            dropped: false,
            startX: evt.pageX,
            startY: evt.pageY,
            startTime: new Date().getTime()
        };

        $(params.listSelector).addClass(params.targetListClass);

        // Create a proxy - the copy of the dragged element that moves together with a mouse.
        var offset = movedElement.offset();
        proxy = movedElement.clone();
        movedElement.parent().append(proxy);
        proxy.removeAttr('id').addClass(params.isDraggedClass).css({position: 'fixed'});
        movedElement.addClass(params.currentPositionClass);
        proxy.offset(offset);
        proxyDelta = {x: offset.left - evt.pageX, y: offset.top - evt.pageY};

        // Start drag.
        $('body').on('mousemove touchmove mouseup touchend', dragHandler);
        $('body').on('keypress', dragcancelHandler);

        // Start autoscrolling. Every time the page is scrolled emulate the mousemove event.
        autoScroll.start(function() {
            $('body').trigger('mousemove');
        });

        executeCallback('dragstart');
    };

    /**
     *
     * @param {Number} pageX
     * @param {Number} pageY
     * @param {jQuery} element
     * @returns {Object}|null
     */
    var getPositionInNode = function(pageX, pageY, element) {
        if (!element.length) {
            return null;
        }
        var node = element[0],
            offset = 0,
            rect = node.getBoundingClientRect(),
            y = pageY - (rect.top + window.scrollY),
            x = pageX - (rect.left + window.scrollX);
        if (x >= -offset && x <= rect.width + offset && y >= -offset && y <= rect.height + offset) {
            return {
                x: x,
                y: y,
                xRatio: rect.width ? (x / rect.width) : 0,
                yRatio: rect.height ? (y / rect.height) : 0
            };
        }
        return null;
    };

    /**
     * Callback for filter that checks that current element is not proxy
     *
     * @return {boolean}
     */
    var isNotProxy = function() {
        return this !== proxy[0];
    };

    /**
     * Handler for events mousemove touchmove mouseup touchend
     *
     * @param {Event} evt
     */
    var dragHandler = function(evt) {

        calculatePositionOnPage(evt);

        // We can not use evt.target here because it will most likely be our proxy.
        // Move the proxy out of the way so we can find the element at the current mouse position.
        proxy.offset({top: -1000, left: -1000});
        // Find the element at the current mouse position.
        var element = $(document.elementFromPoint(evt.clientX, evt.clientY));

        // Find the list element and the list over the mouse position.
        var current = element.closest(params.listSelector + ' > :not(.' + params.isDraggedClass + ')'),
            currentList = element.closest(params.listSelector);

        // Add the specified class to the list element we are hovering.
        $('.' + params.overElementClass).removeClass(params.overElementClass);
        current.addClass(params.overElementClass);

        // Move proxy to the current position.
        proxy.offset({top: proxyDelta.y + evt.pageY, left: proxyDelta.x + evt.pageX});

        if (currentList.length && !currentList.children().filter(isNotProxy).length) {
            // Mouse is over an empty list.
            moveDraggedElement(currentList, $());
        } else if (current.length === 1) {
            // Mouse is over an element in a list - find whether we should move the current position
            // above or below this element.
            var coordinates = getPositionInNode(evt.pageX, evt.pageY, current);
            if (coordinates) {
                var parent = current.parent(),
                    ratio = params.isHorizontal ? coordinates.xRatio : coordinates.yRatio;
                if (ratio > 0.5) {
                    // Insert after this element.
                    moveDraggedElement(parent, current.next().filter(isNotProxy));
                } else {
                    // Insert before this element.
                    moveDraggedElement(parent, current);
                }
            }
        }

        if (evt.type === 'mouseup' || evt.type === 'touchend') {
            // Drop the moved element.
            info.endX = evt.pageX;
            info.endY = evt.pageY;
            info.endTime = new Date().getTime();
            info.dropped = true;
            executeCallback('drop');
            finishDragging();
        }
    };

    /**
     * Moves the current position of the dragged element
     *
     * @param {jQuery} parentElement
     * @param {jQuery} beforeElement
     */
    var moveDraggedElement = function(parentElement, beforeElement) {
        var dragEl = info.draggedElement;
        if (beforeElement.length && beforeElement[0] === dragEl[0]) {
            // Insert before the current position of the dragged element - nothing to do.
            return;
        }
        if (parentElement[0] === info.targetList[0] &&
                beforeElement.length === info.targetNextElement.length &&
                beforeElement[0] === info.targetNextElement[0]) {
            // Insert in the same location as the current position - nothing to do.
            return;
        }

        if (beforeElement.length) {
            // Move the dragged element before the specified element.
            parentElement[0].insertBefore(dragEl[0], beforeElement[0]);
        } else if (proxy.parent().length && proxy.parent()[0] === parentElement[0]) {
            // We need to move to the end of the list but the last element in this list is a proxy.
            // Always leave the proxy in the end of the list.
            parentElement[0].insertBefore(dragEl[0], proxy[0]);
        } else {
            // Insert in the end of a list (when proxy is in another list).
            parentElement[0].appendChild(dragEl[0]);
        }

        // Save the current position of the dragged element in the list.
        info.targetList = parentElement;
        info.targetNextElement = beforeElement;
        executeCallback('drag');
    };

    /**
     * Finish dragging (when dropped or cancelled).
     */
    var finishDragging = function() {
        resetDraggedClasses();
        autoScroll.stop();
        $('body').off('mousemove touchmove mouseup touchend', dragHandler);
        $('body').off('keypress', dragcancelHandler);
        proxy.remove();
        proxy = null;
        executeCallback('dragend');
        info = null;
    };

    /**
     * Executes callback specified in sortable list parameters
     *
     * @param {String} eventName
     */
    var executeCallback = function(eventName) {
        info.draggedElement.trigger('sortablelist-' + eventName, info);

        /*var callback = params[callbackName];
        if (callback !== null) {
            callback(info);
        }
        */
    };

    /**
     * Handler from keypress event (cancel dragging when Esc is pressed)
     *
     * @param {Event} evt
     */
    var dragcancelHandler = function(evt) {
        if (evt.type !== 'keypress' || evt.originalEvent.keycode !== 27) {
            // Only cancel dragging when Esc was pressed.
            return;
        }
        // Dragging was cancelled. Return item to the original position.
        moveDraggedElement(info.sourceList, info.sourceNextElement);
        finishDragging();
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
