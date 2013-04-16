YUI.add('moodle-course-categoryexpander', function (Y, NAME) {

/**
 * Adds toggling of subcategory with automatic loading using AJAX.
 *
 * This also includes application of an animation to improve user experience.
 *
 * @module moodle-course-categoryexpander
 */

/**
 * The course category expander.
 *
 * @constructor
 * @class Y.Moodle.course.categoryexpander
 */

var CSS = {
        CONTENTNODE: 'content',
        COLLAPSEALL: 'collapse-all',
        LOADED: 'loaded',
        NOTLOADED: 'notloaded',
        SECTIONCOLLAPSED: 'collapsed',
        HASCHILDREN: 'with_children'
    },
    SELECTORS = {
        LOADEDTREES: '.with_children.loaded',
        CONTENTNODE: '.content',
        CATEGORYLISTENLINK: '.category .info .name',
        CATEGORYSPINNERLOCATION: '.name',
        CATEGORYWITHCOLLAPSEDLOADEDCHILDREN: '.category.with_children.loaded.collapsed',
        CATEGORYWITHMAXIMISEDLOADEDCHILDREN: '.category.with_children.loaded:not(.collapsed)',
        COLLAPSEEXPAND: '.collapseexpand',
        COURSEBOX: '.coursebox',
        COURSEBOXLISTENLINK: '.coursebox .moreinfo',
        COURSEBOXSPINNERLOCATION: '.name a',
        COURSECATEGORYTREE: '.course_category_tree',
        PARENTWITHCHILDREN: '.category'
    },
    NS = Y.namespace('Moodle.course.categoryexpander'),
    TYPE_CATEGORY = 0,
    TYPE_COURSE = 1,
    URL = M.cfg.wwwroot + '/course/category.ajax.php';

/**
 * Set up the category expander.
 *
 * No arguments are required.
 *
 * @method init
 */
NS.init = function() {
    Y.one(Y.config.doc).delegate('click', this.toggle_category_expansion, SELECTORS.CATEGORYLISTENLINK, this);
    Y.one(Y.config.doc).delegate('click', this.toggle_coursebox_expansion, SELECTORS.COURSEBOXLISTENLINK, this);
    Y.one(Y.config.doc).delegate('click', this.collapse_expand_all, SELECTORS.COLLAPSEEXPAND, this);
};

/**
 * Toggle the animation of the clicked category node.
 *
 * @method toggle_category_expansion
 * @private
 * @param {EventFacade} e
 */
NS.toggle_category_expansion = function(e) {
    // Load the actual dependencies now that we've been called.
    Y.use('io-base', 'json-parse', 'moodle-core-notification', 'anim', function() {
        // Overload the toggle_category_expansion with the _toggle_category_expansion function to ensure that
        // this function isn't called in the future, and call it for the first time.
        NS.toggle_category_expansion = NS._toggle_category_expansion;
        NS.toggle_category_expansion(e);
    });
};

/**
 * Toggle the animation of the clicked coursebox node.
 *
 * @method toggle_coursebox_expansion
 * @private
 * @param {EventFacade} e
 */
NS.toggle_coursebox_expansion = function(e) {
    // Load the actual dependencies now that we've been called.
    Y.use('io-base', 'json-parse', 'moodle-core-notification', 'anim', function() {
        // Overload the toggle_coursebox_expansion with the _toggle_coursebox_expansion function to ensure that
        // this function isn't called in the future, and call it for the first time.
        NS.toggle_coursebox_expansion = NS._toggle_coursebox_expansion;
        NS.toggle_coursebox_expansion(e);
    });

    e.preventDefault();
};

NS._toggle_coursebox_expansion = function(e) {
    var courseboxnode;

    // Grab the parent category container - this is where the new content will be added.
    courseboxnode = e.target.ancestor(SELECTORS.COURSEBOX, true);
    e.preventDefault();

    if (courseboxnode.hasClass(CSS.LOADED)) {
        // We've already loaded this content so we just need to toggle the view of it.
        this.run_expansion(courseboxnode);
        return;
    }

    this._toggle_generic_expansion({
        parentnode: courseboxnode,
        childnode: courseboxnode.one(SELECTORS.CONTENTNODE),
        spinnerhandle: SELECTORS.COURSEBOXSPINNERLOCATION,
        data: {
            courseid: courseboxnode.getData('courseid'),
            type: TYPE_COURSE
        }
    });
};

NS._toggle_category_expansion = function(e) {
    var categorynode,
        categoryid,
        depth;

    if (e.target.test('a') || e.target.test('img')) {
        // Return early if either an anchor or an image were clicked.
        return;
    }

    // Grab the parent category container - this is where the new content will be added.
    categorynode = e.target.ancestor(SELECTORS.PARENTWITHCHILDREN, true);

    if (!categorynode.hasClass(CSS.HASCHILDREN)) {
        // Nothing to do here - this category has no children.
        return;
    }

    if (categorynode.hasClass(CSS.LOADED)) {
        // We've already loaded this content so we just need to toggle the view of it.
        this.run_expansion(categorynode);
        return;
    }

    // We use Data attributes to store the category.
    categoryid = categorynode.getData('categoryid');
    depth = categorynode.getData('depth');
    if (typeof categoryid === "undefined" || typeof depth === "undefined") {
        return;
    }

    this._toggle_generic_expansion({
        parentnode: categorynode,
        childnode: categorynode.one(SELECTORS.CONTENTNODE),
        spinnerhandle: SELECTORS.CATEGORYSPINNERLOCATION,
        data: {
            categoryid: categoryid,
            depth: depth,
            showcourses: categorynode.getData('showcourses'),
            type: TYPE_CATEGORY
        }
    });
};

/**
 * Wrapper function to handle toggling of generic types.
 *
 * @method _toggle_generic_expansion
 * @private
 * @param {Object} config
 */
NS._toggle_generic_expansion = function(config) {
    if (config.spinnerhandle) {
      // Add a spinner to give some feedback to the user.
      spinner = M.util.add_spinner(Y, config.parentnode.one(config.spinnerhandle)).show();
    }

    // Fetch the data.
    Y.io(URL, {
        method: 'POST',
        context: this,
        on: {
            complete: this.process_results
        },
        data: config.data,
        "arguments": {
            parentnode: config.parentnode,
            childnode: config.childnode,
            spinner: spinner
        }
    });
};

/**
 * Apply the animation on the supplied node.
 *
 * @method run_expansion
 * @private
 * @param {Node} categorynode The node to apply the animation to
 */
NS.run_expansion = function(categorynode) {
    var categorychildren = categorynode.one(SELECTORS.CONTENTNODE),
        self = this,
        ancestor = categorynode.ancestor(SELECTORS.COURSECATEGORYTREE);

    // Add our animation to the categorychildren.
    this.add_animation(categorychildren);


    // If we already have the class, remove it before showing otherwise we perform the
    // animation whilst the node is hidden.
    if (categorynode.hasClass(CSS.SECTIONCOLLAPSED)) {
        // To avoid a jump effect, we need to set the height of the children to 0 here before removing the SECTIONCOLLAPSED class.
        categorychildren.setStyle('height', '0');
        categorynode.removeClass(CSS.SECTIONCOLLAPSED);
        categorychildren.fx.set('reverse', false);
    } else {
        categorychildren.fx.set('reverse', true);
        categorychildren.fx.once('end', function(e, categorynode) {
            categorynode.addClass(CSS.SECTIONCOLLAPSED);
        }, this, categorynode);
    }

    categorychildren.fx.once('end', function(e, categorychildren) {
        // Remove the styles that the animation has set.
        categorychildren.setStyles({
            height: '',
            opacity: ''
        });

        // To avoid memory gobbling, remove the animation. It will be added back if called again.
        this.destroy();
        self.update_collapsible_actions(ancestor);
    }, categorychildren.fx, categorychildren);

    // Now that everything has been set up, run the animation.
    categorychildren.fx.run();
};

NS.collapse_expand_all = function(e) {
    var ancestor = e.currentTarget.ancestor(SELECTORS.COURSECATEGORYTREE),
        collapseall = ancestor.one(SELECTORS.COLLAPSEEXPAND);
    if (collapseall.hasClass(CSS.COLLAPSEALL)) {
        this.collapse_all(ancestor);
    } else {
        this.expand_all(ancestor);
    }
    this.update_collapsible_actions(ancestor);
};

NS.expand_all = function(ancestor) {
    // We need to expand their children before we expand them to make
    // things easier for adding the animations.
    ancestor.all(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)
        .each(function(c) {
        if (c.ancestor(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)) {
            // We can just open this one - it's hidden from view
            c.removeClass(CSS.SECTIONCOLLAPSED);
            c.all(SELECTORS.LOADEDTREES).removeClass(CSS.SECTIONCOLLAPSED);
        } else {
            this.run_expansion(c);
        }
    }, this);
};

NS.collapse_all = function(ancestor) {
    ancestor.all(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN)
        .each(function(c) {
        if (c.ancestor(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN)) {
            // We can just open this one - it's hidden from view
            c.addClass(CSS.SECTIONCOLLAPSED);
            c.all(SELECTORS.LOADEDTREES).addClass(CSS.SECTIONCOLLAPSED);
        } else {
            this.run_expansion(c);
        }
    }, this);
};

NS.update_collapsible_actions = function(ancestor) {
    var foundchildren = false,
        // Grab the anchor for the collapseexpand all link.
        togglelink = ancestor.one(SELECTORS.COLLAPSEEXPAND);

    if (!togglelink) {
        // We should always have a togglelink but ensure.
        return;
    }

    // Search for any visibly expanded children.
    ancestor.all(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN).each(function(n) {
        // If we can find any collapsed ancestors, skip.
        if (n.ancestor(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)) {
            return false;
        }
        foundchildren = true;
        return true;
    });

    if (foundchildren) {
        // At least one maximised child found. Show the collapseall.
        togglelink.setHTML(M.util.get_string('collapseall', 'moodle'))
            .addClass(CSS.COLLAPSEALL);
    } else {
        // No maximised children found. Show the expandall.
        togglelink.setHTML(M.util.get_string('expandall', 'moodle'))
            .removeClass(CSS.COLLAPSEALL);
    }
};

/**
 * Process the data returned by Y.io.
 * This includes appending it to the relevant part of the DOM, and applying our animations.
 *
 * @method process_results
 * @private
 * @param {String} tid The Transaction ID
 * @param {Object} response The Reponse returned by Y.IO
 * @param {Object} ioargs The additional arguments provided by Y.IO
 */
NS.process_results = function(tid, response, args) {
    var newnode,
        data;
    try {
        data = Y.JSON.parse(response.responseText);
        if (data.error) {
            return new M.core.ajaxException(data);
        }
    } catch (e) {
        return new M.core.exception(e);
    }

    // Insert the returned data into a new Node.
    newnode = Y.Node.create(data);

    // Append to the existing child location.
    args.childnode.appendChild(newnode);

    // Now that we have content, we can swap the classes on the toggled container.
    args.parentnode
        .addClass(CSS.LOADED)
        .removeClass(CSS.NOTLOADED);

    // Toggle the open/close status of the node now that it's content has been loaded.
    this.run_expansion(args.parentnode);

    // Remove the spinner now that we've started to show the content.
    if (args.spinner) {
        args.spinner.hide().destroy();
    }
};

/**
 * Add our animation to the Node.
 *
 * @method add_animation
 * @private
 * @param {Node} childnode
 */
NS.add_animation = function(childnode) {
    if (typeof childnode.fx !== "undefined") {
        // The animation has already been plugged to this node.
        return childnode;
    }

    childnode.plug(Y.Plugin.NodeFX, {
        from: {
            height: 0,
            opacity: 0
        },
        to: {
            // This sets a dynamic height in case the node content changes.
            height: function(node) {
                // Get expanded height (offsetHeight may be zero).
                return node.get('scrollHeight');
            },
            opacity: 1
        },
        duration: 0.2
    });

    return childnode;
};


}, '@VERSION@', {"requires": ["node"]});
