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
define(['jquery', 'core/log'], function($, log) {
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
     * Resets the temporary classes assigned during dragging
     *
     * @param {Object} params
     */
    var resetDraggedClasses = function(params) {
        var lists = $(params.listSelector);
        lists.children()
            .removeClass(params.isDraggedClass)
            .removeClass(params.currentPositionClass);
        lists
            .removeClass(params.targetListClass)
            .removeClass(params.sourceListClass);
    };

    /**
     * Stores the last element where the mousedown event has occurred
     *
     * This information is not present in dragstart event, but it is important for us when we want to allow
     * dragging only by the specified handle.
     *
     * @type {DOMElement}
     */
    var mouseTarget = null;

    /**
     * Handler from mousedown event.
     *
     * @param {Event} evt
     */
    var mousedownHandler = function(evt) {
        mouseTarget = evt.target;
    };

    /**
     * Handler from dragstart event
     *
     * @param {Event} evt The dragstart event
     */
    var dragstartHandler = function(evt) {
        var params = evt.data.params;
        resetDraggedClasses(params);

        // Check that this element belongs to any of registered sortable lists.
        var movedElement = $(evt.target);

        // Check that we grabbed the element by the handle.
        var moveHandlerSelector = params.moveHandlerSelector;
        if (moveHandlerSelector !== null) {
            // Dragstart event only returns [draggable] element as target. We want to check if the handler
            // was dragged. The target of the last mousedown event is remembered in mouseTarget.
            if (!$(mouseTarget).closest(moveHandlerSelector, movedElement).length) {
                return;
            }
        }

        // Information about moved element with original location.
        evt.data.info = {
            draggedElement: movedElement,
            sourceNextElement: movedElement.next(),
            sourceList: movedElement.parent(),
            targetNextElement: movedElement.next(),
            targetList: movedElement.parent(),
            dropped: false
        };

        // Limit drag effect.
        evt.originalEvent.dataTransfer.effectAllowed = 'move';
        // Remember the dragged element in the element.
        evt.originalEvent.dataTransfer.setData('text/plain', 'sortable-list');

        // Add listeners. User can drag and drop the item anywhere on the document,
        // we will place it in the last known position while they were dragging over the list.
        $('body').on('dragover dragleave dragenter', evt.data, dragoverHandler);
        $('body').on('drop dragdrop', evt.data, dropHandler);
        $('body').on('dragend', evt.data, dragendHandler);

        // Set the classes.
        $(params.listSelector).addClass(params.targetListClass);
        movedElement.addClass(params.isDraggedClass);
        setTimeout(function() {
            // HTML5 will create a copy of the dragged element to show as the one being dragged. If we alter the classes
            // after the timeout it will apply to the current position of the element but not the copy created by d&d.
            movedElement
                .removeClass(params.isDraggedClass)
                .addClass(params.currentPositionClass);
            executeCallback('onDragCancel', evt);
        }, 0);
    };

    /**
     * Finds the item in the list at the drag position
     *
     * @param {Event} evt One of drag events
     * @return {jQuery}
     */
    var getDragoverListItem = function(evt) {
        var el = $(evt.target),
            listSelector = evt.data.params.listSelector;
        if ($.contains(evt.data.info.draggedElement[0], evt.target)) {
            // We are dragging over the current position of the dragged element.
            return $();
        }
        if (!el.closest(listSelector).length || el.is(listSelector)) {
            // Element has to be inside list but not be the list itself.
            return $();
        }
        while (el.length) {
            var parent = el.parent();
            if (parent.is(evt.data.params.listSelector)) {
                return el;
            }
            el = parent;
        }
        return $();
    };

    /**
     * Checks if current position of the item needs to be changed
     *
     * @param {Event} evt One of drag events
     */
    var processChangeDragPosition = function(evt) {
        var list = $(evt.target).closest(evt.data.params.listSelector);
        if (!list.length) {
            // We are not over sortable list.
            return;
        }
        var target = getDragoverListItem(evt);
        if (target.length) {
            var rect = target[0].getBoundingClientRect();
            var next = (evt.pageY - target.offset().top) / (rect.bottom - rect.top) > 0.5;
            if (next) {
                // Insert after the target.
                moveDraggedElement(evt, list, target.next());
            } else {
                // Insert before the target.
                moveDraggedElement(evt, list, target);
            }
        } else if (!list.children().length) {
            // Dragging over an empty list that is also a potential destination.
            moveDraggedElement(evt, list, $());
        }
    };

    /**
     * Listenere for dragover, dragenter and dragleave events.
     *
     * @param {Event} evt One of drag events
     */
    var dragoverHandler = function(evt) {
        evt.preventDefault();
        evt.originalEvent.dataTransfer.dropEffect = 'move';
        if (evt.type !== 'dragleave') {
            // Do processing in a separate thread, otherwise there may be problems when user drags too fast.
            setTimeout(function() {
                processChangeDragPosition(evt);
            }, 0);
        }
    };

    /**
     * Moves the current position of the dragged element
     *
     * @param {Event} evt one of drag&drop events
     * @param {jQuery} parentElement
     * @param {jQuery} beforeElement
     */
    var moveDraggedElement = function(evt, parentElement, beforeElement) {
        var info = evt.data.info,
            dragEl = info.draggedElement,
            isSame = parentElement[0] === info.targetList[0] &&
                beforeElement.length === info.targetNextElement.length &&
                beforeElement[0] === info.targetNextElement[0];
        if (isSame) {
            return;
        }
        if (beforeElement.length) {
            parentElement[0].insertBefore(dragEl[0], beforeElement[0]);
        } else {
            parentElement[0].appendChild(dragEl[0]);
        }
        info.targetList = parentElement;
        info.targetNextElement = beforeElement;
        executeCallback('onMove', evt);
    };

    /**
     * Handler for drop event
     *
     * @param {Event} evt The drop or dragdrop event
     */
    var dropHandler = function(evt) {
        evt.preventDefault();
        evt.data.info.dropped = true;
        processChangeDragPosition(evt);
        executeCallback('onDrop', evt);
    };

    /**
     * Executes callback specified in sortable list parameters
     *
     * @param {String} callbackName
     * @param {Event} evt
     */
    var executeCallback = function(callbackName, evt) {
        var callback = evt.data.params[callbackName];
        if (callback !== null) {
            callback(evt.data.info);
        }
    };

    /**
     * Handler for dragend event. Clear the classes and listeners
     *
     * @param {Event} evt
     */
    var dragendHandler = function(evt) {
        evt.preventDefault();

        var info = evt.data.info;
        if (!info.dropped) {
            // Dragging was cancelled. Return item to the original position.
            moveDraggedElement(evt, info.sourceList, info.sourceNextElement);
            executeCallback('onDragCancel', evt);
        }

        $('body').off('dragover dragleave dragenter', dragoverHandler);
        $('body').off('drop dragdrop', dropHandler);
        $('body').off('dragend', dragendHandler);

        resetDraggedClasses(evt.data.params);
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
            $(params.listSelector).on('dragstart', {params: params}, dragstartHandler);
            if (params.moveHandlerSelector !== null) {
                $(params.listSelector).on('mousedown', mousedownHandler);
            }
        }
    };
});
