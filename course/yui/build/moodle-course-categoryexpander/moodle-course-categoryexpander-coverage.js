if (typeof _yuitest_coverage == "undefined"){
    _yuitest_coverage = {};
    _yuitest_coverline = function(src, line){
        var coverage = _yuitest_coverage[src];
        if (!coverage.lines[line]){
            coverage.calledLines++;
        }
        coverage.lines[line]++;
    };
    _yuitest_coverfunc = function(src, name, line){
        var coverage = _yuitest_coverage[src],
            funcId = name + ":" + line;
        if (!coverage.functions[funcId]){
            coverage.calledFunctions++;
        }
        coverage.functions[funcId]++;
    };
}
_yuitest_coverage["build/moodle-course-categoryexpander/moodle-course-categoryexpander.js"] = {
    lines: {},
    functions: {},
    coveredLines: 0,
    calledLines: 0,
    coveredFunctions: 0,
    calledFunctions: 0,
    path: "build/moodle-course-categoryexpander/moodle-course-categoryexpander.js",
    code: []
};
_yuitest_coverage["build/moodle-course-categoryexpander/moodle-course-categoryexpander.js"].code=["YUI.add('moodle-course-categoryexpander', function (Y, NAME) {","","/**"," * Adds toggling of subcategory with automatic loading using AJAX."," *"," * This also includes application of an animation to improve user experience."," *"," * @module moodle-course-categoryexpander"," */","","/**"," * The course category expander."," *"," * @constructor"," * @class Y.Moodle.course.categoryexpander"," */","","var CSS = {","        CONTENTNODE: 'content',","        COLLAPSEALL: 'collapse-all',","        LOADED: 'loaded',","        NOTLOADED: 'notloaded',","        SECTIONCOLLAPSED: 'collapsed',","        HASCHILDREN: 'with_children'","    },","    SELECTORS = {","        LOADEDTREES: '.with_children.loaded',","        CONTENTNODE: '.content',","        CATEGORYLISTENLINK: '.category .info .name',","        CATEGORYSPINNERLOCATION: '.name',","        CATEGORYWITHCOLLAPSEDLOADEDCHILDREN: '.category.with_children.loaded.collapsed',","        CATEGORYWITHMAXIMISEDLOADEDCHILDREN: '.category.with_children.loaded:not(.collapsed)',","        COLLAPSEEXPAND: '.collapseexpand',","        COURSEBOX: '.coursebox',","        COURSEBOXLISTENLINK: '.coursebox .moreinfo',","        COURSEBOXSPINNERLOCATION: '.name a',","        COURSECATEGORYTREE: '.course_category_tree',","        PARENTWITHCHILDREN: '.category'","    },","    NS = Y.namespace('Moodle.course.categoryexpander'),","    TYPE_CATEGORY = 0,","    TYPE_COURSE = 1,","    URL = M.cfg.wwwroot + '/course/category.ajax.php';","","/**"," * Set up the category expander."," *"," * No arguments are required."," *"," * @method init"," */","NS.init = function() {","    Y.one(Y.config.doc).delegate('click', this.toggle_category_expansion, SELECTORS.CATEGORYLISTENLINK, this);","    Y.one(Y.config.doc).delegate('click', this.toggle_coursebox_expansion, SELECTORS.COURSEBOXLISTENLINK, this);","    Y.one(Y.config.doc).delegate('click', this.collapse_expand_all, SELECTORS.COLLAPSEEXPAND, this);","    Y.all(SELECTORS.COLLAPSEEXPAND).each(function(el){","        var ancestor = el.ancestor(SELECTORS.COURSECATEGORYTREE);","        if (ancestor) {","            NS.update_collapsible_actions(ancestor);","        }","    });","};","","/**"," * Toggle the animation of the clicked category node."," *"," * @method toggle_category_expansion"," * @private"," * @param {EventFacade} e"," */","NS.toggle_category_expansion = function(e) {","    // Load the actual dependencies now that we've been called.","    Y.use('io-base', 'json-parse', 'moodle-core-notification', 'anim', function() {","        // Overload the toggle_category_expansion with the _toggle_category_expansion function to ensure that","        // this function isn't called in the future, and call it for the first time.","        NS.toggle_category_expansion = NS._toggle_category_expansion;","        NS.toggle_category_expansion(e);","    });","};","","/**"," * Toggle the animation of the clicked coursebox node."," *"," * @method toggle_coursebox_expansion"," * @private"," * @param {EventFacade} e"," */","NS.toggle_coursebox_expansion = function(e) {","    // Load the actual dependencies now that we've been called.","    Y.use('io-base', 'json-parse', 'moodle-core-notification', 'anim', function() {","        // Overload the toggle_coursebox_expansion with the _toggle_coursebox_expansion function to ensure that","        // this function isn't called in the future, and call it for the first time.","        NS.toggle_coursebox_expansion = NS._toggle_coursebox_expansion;","        NS.toggle_coursebox_expansion(e);","    });","","    e.preventDefault();","};","","NS._toggle_coursebox_expansion = function(e) {","    var courseboxnode;","","    // Grab the parent category container - this is where the new content will be added.","    courseboxnode = e.target.ancestor(SELECTORS.COURSEBOX, true);","    e.preventDefault();","","    if (courseboxnode.hasClass(CSS.LOADED)) {","        // We've already loaded this content so we just need to toggle the view of it.","        this.run_expansion(courseboxnode);","        return;","    }","","    this._toggle_generic_expansion({","        parentnode: courseboxnode,","        childnode: courseboxnode.one(SELECTORS.CONTENTNODE),","        spinnerhandle: SELECTORS.COURSEBOXSPINNERLOCATION,","        data: {","            courseid: courseboxnode.getData('courseid'),","            type: TYPE_COURSE","        }","    });","};","","NS._toggle_category_expansion = function(e) {","    var categorynode,","        categoryid,","        depth;","","    if (e.target.test('a') || e.target.test('img')) {","        // Return early if either an anchor or an image were clicked.","        return;","    }","","    // Grab the parent category container - this is where the new content will be added.","    categorynode = e.target.ancestor(SELECTORS.PARENTWITHCHILDREN, true);","","    if (!categorynode.hasClass(CSS.HASCHILDREN)) {","        // Nothing to do here - this category has no children.","        return;","    }","","    if (categorynode.hasClass(CSS.LOADED)) {","        // We've already loaded this content so we just need to toggle the view of it.","        this.run_expansion(categorynode);","        return;","    }","","    // We use Data attributes to store the category.","    categoryid = categorynode.getData('categoryid');","    depth = categorynode.getData('depth');","    if (typeof categoryid === \"undefined\" || typeof depth === \"undefined\") {","        return;","    }","","    this._toggle_generic_expansion({","        parentnode: categorynode,","        childnode: categorynode.one(SELECTORS.CONTENTNODE),","        spinnerhandle: SELECTORS.CATEGORYSPINNERLOCATION,","        data: {","            categoryid: categoryid,","            depth: depth,","            showcourses: categorynode.getData('showcourses'),","            type: TYPE_CATEGORY","        }","    });","};","","/**"," * Wrapper function to handle toggling of generic types."," *"," * @method _toggle_generic_expansion"," * @private"," * @param {Object} config"," */","NS._toggle_generic_expansion = function(config) {","    if (config.spinnerhandle) {","      // Add a spinner to give some feedback to the user.","      spinner = M.util.add_spinner(Y, config.parentnode.one(config.spinnerhandle)).show();","    }","","    // Fetch the data.","    Y.io(URL, {","        method: 'POST',","        context: this,","        on: {","            complete: this.process_results","        },","        data: config.data,","        \"arguments\": {","            parentnode: config.parentnode,","            childnode: config.childnode,","            spinner: spinner","        }","    });","};","","/**"," * Apply the animation on the supplied node."," *"," * @method run_expansion"," * @private"," * @param {Node} categorynode The node to apply the animation to"," */","NS.run_expansion = function(categorynode) {","    var categorychildren = categorynode.one(SELECTORS.CONTENTNODE),","        self = this,","        ancestor = categorynode.ancestor(SELECTORS.COURSECATEGORYTREE);","","    // Add our animation to the categorychildren.","    this.add_animation(categorychildren);","","","    // If we already have the class, remove it before showing otherwise we perform the","    // animation whilst the node is hidden.","    if (categorynode.hasClass(CSS.SECTIONCOLLAPSED)) {","        // To avoid a jump effect, we need to set the height of the children to 0 here before removing the SECTIONCOLLAPSED class.","        categorychildren.setStyle('height', '0');","        categorynode.removeClass(CSS.SECTIONCOLLAPSED);","        categorychildren.fx.set('reverse', false);","    } else {","        categorychildren.fx.set('reverse', true);","        categorychildren.fx.once('end', function(e, categorynode) {","            categorynode.addClass(CSS.SECTIONCOLLAPSED);","        }, this, categorynode);","    }","","    categorychildren.fx.once('end', function(e, categorychildren) {","        // Remove the styles that the animation has set.","        categorychildren.setStyles({","            height: '',","            opacity: ''","        });","","        // To avoid memory gobbling, remove the animation. It will be added back if called again.","        this.destroy();","        self.update_collapsible_actions(ancestor);","    }, categorychildren.fx, categorychildren);","","    // Now that everything has been set up, run the animation.","    categorychildren.fx.run();","};","","NS.collapse_expand_all = function(e) {","    var ancestor = e.currentTarget.ancestor(SELECTORS.COURSECATEGORYTREE);","    if (!ancestor) {","        return;","    }","    var collapseall = ancestor.one(SELECTORS.COLLAPSEEXPAND);","    if (collapseall.hasClass(CSS.COLLAPSEALL)) {","        this.collapse_all(ancestor);","    } else {","        this.expand_all(ancestor);","    }","    this.update_collapsible_actions(ancestor);","};","","NS.expand_all = function(ancestor) {","    // We need to expand their children before we expand them to make","    // things easier for adding the animations.","    ancestor.all(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)","        .each(function(c) {","        if (c.ancestor(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)) {","            // We can just open this one - it's hidden from view","            c.removeClass(CSS.SECTIONCOLLAPSED);","            c.all(SELECTORS.LOADEDTREES).removeClass(CSS.SECTIONCOLLAPSED);","        } else {","            this.run_expansion(c);","        }","    }, this);","};","","NS.collapse_all = function(ancestor) {","    ancestor.all(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN)","        .each(function(c) {","        if (c.ancestor(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN)) {","            // We can just open this one - it's hidden from view","            c.addClass(CSS.SECTIONCOLLAPSED);","            c.all(SELECTORS.LOADEDTREES).addClass(CSS.SECTIONCOLLAPSED);","        } else {","            this.run_expansion(c);","        }","    }, this);","};","","NS.update_collapsible_actions = function(ancestor) {","    var foundchildren = false,","        // Grab the anchor for the collapseexpand all link.","        togglelink = ancestor.one(SELECTORS.COLLAPSEEXPAND);","","    if (!togglelink) {","        // We should always have a togglelink but ensure.","        return;","    }","","    // Search for any visibly expanded children.","    ancestor.all(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN).each(function(n) {","        // If we can find any collapsed ancestors, skip.","        if (n.ancestor(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)) {","            return false;","        }","        foundchildren = true;","        return true;","    });","","    if (foundchildren) {","        // At least one maximised child found. Show the collapseall.","        togglelink.setHTML(M.util.get_string('collapseall', 'moodle'))","            .addClass(CSS.COLLAPSEALL);","        togglelink.removeClass('hiddenifjs');","    } else if (ancestor.all(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN).size()) {","        // No maximised children found but there are collapsed children. Show the expandall.","        togglelink.setHTML(M.util.get_string('expandall', 'moodle'))","            .removeClass(CSS.COLLAPSEALL);","        togglelink.removeClass('hiddenifjs');","    } else {","        // Nothing can be either expanded or collapsed","        togglelink.addClass('hiddenifjs');","    }","};","","/**"," * Process the data returned by Y.io."," * This includes appending it to the relevant part of the DOM, and applying our animations."," *"," * @method process_results"," * @private"," * @param {String} tid The Transaction ID"," * @param {Object} response The Reponse returned by Y.IO"," * @param {Object} ioargs The additional arguments provided by Y.IO"," */","NS.process_results = function(tid, response, args) {","    var newnode,","        data;","    try {","        data = Y.JSON.parse(response.responseText);","        if (data.error) {","            return new M.core.ajaxException(data);","        }","    } catch (e) {","        return new M.core.exception(e);","    }","","    // Insert the returned data into a new Node.","    newnode = Y.Node.create(data);","","    // Append to the existing child location.","    args.childnode.appendChild(newnode);","","    // Now that we have content, we can swap the classes on the toggled container.","    args.parentnode","        .addClass(CSS.LOADED)","        .removeClass(CSS.NOTLOADED);","","    // Toggle the open/close status of the node now that it's content has been loaded.","    this.run_expansion(args.parentnode);","","    // Remove the spinner now that we've started to show the content.","    if (args.spinner) {","        args.spinner.hide().destroy();","    }","};","","/**"," * Add our animation to the Node."," *"," * @method add_animation"," * @private"," * @param {Node} childnode"," */","NS.add_animation = function(childnode) {","    if (typeof childnode.fx !== \"undefined\") {","        // The animation has already been plugged to this node.","        return childnode;","    }","","    childnode.plug(Y.Plugin.NodeFX, {","        from: {","            height: 0,","            opacity: 0","        },","        to: {","            // This sets a dynamic height in case the node content changes.","            height: function(node) {","                // Get expanded height (offsetHeight may be zero).","                return node.get('scrollHeight');","            },","            opacity: 1","        },","        duration: 0.2","    });","","    return childnode;","};","","","}, '@VERSION@', {\"requires\": [\"node\"]});"];
_yuitest_coverage["build/moodle-course-categoryexpander/moodle-course-categoryexpander.js"].lines = {"1":0,"18":0,"52":0,"53":0,"54":0,"55":0,"56":0,"57":0,"58":0,"59":0,"71":0,"73":0,"76":0,"77":0,"88":0,"90":0,"93":0,"94":0,"97":0,"100":0,"101":0,"104":0,"105":0,"107":0,"109":0,"110":0,"113":0,"124":0,"125":0,"129":0,"131":0,"135":0,"137":0,"139":0,"142":0,"144":0,"145":0,"149":0,"150":0,"151":0,"152":0,"155":0,"175":0,"176":0,"178":0,"182":0,"204":0,"205":0,"210":0,"215":0,"217":0,"218":0,"219":0,"221":0,"222":0,"223":0,"227":0,"229":0,"235":0,"236":0,"240":0,"243":0,"244":0,"245":0,"246":0,"248":0,"249":0,"250":0,"252":0,"254":0,"257":0,"260":0,"262":0,"264":0,"265":0,"267":0,"272":0,"273":0,"275":0,"277":0,"278":0,"280":0,"285":0,"286":0,"290":0,"292":0,"296":0,"298":0,"299":0,"301":0,"302":0,"305":0,"307":0,"309":0,"310":0,"312":0,"314":0,"317":0,"331":0,"332":0,"334":0,"335":0,"336":0,"337":0,"340":0,"344":0,"347":0,"350":0,"355":0,"358":0,"359":0,"370":0,"371":0,"373":0,"376":0,"385":0,"392":0};
_yuitest_coverage["build/moodle-course-categoryexpander/moodle-course-categoryexpander.js"].functions = {"(anonymous 2):56":0,"init:52":0,"(anonymous 3):73":0,"toggle_category_expansion:71":0,"(anonymous 4):90":0,"toggle_coursebox_expansion:88":0,"_toggle_coursebox_expansion:100":0,"_toggle_category_expansion:124":0,"_toggle_generic_expansion:175":0,"(anonymous 5):222":0,"(anonymous 6):227":0,"run_expansion:204":0,"collapse_expand_all:243":0,"(anonymous 7):261":0,"expand_all:257":0,"(anonymous 8):274":0,"collapse_all:272":0,"(anonymous 9):296":0,"update_collapsible_actions:285":0,"process_results:331":0,"height:383":0,"add_animation:370":0,"(anonymous 1):1":0};
_yuitest_coverage["build/moodle-course-categoryexpander/moodle-course-categoryexpander.js"].coveredLines = 117;
_yuitest_coverage["build/moodle-course-categoryexpander/moodle-course-categoryexpander.js"].coveredFunctions = 23;
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 1);
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

_yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 1)", 1);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 18);
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
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 52);
NS.init = function() {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "init", 52);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 53);
Y.one(Y.config.doc).delegate('click', this.toggle_category_expansion, SELECTORS.CATEGORYLISTENLINK, this);
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 54);
Y.one(Y.config.doc).delegate('click', this.toggle_coursebox_expansion, SELECTORS.COURSEBOXLISTENLINK, this);
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 55);
Y.one(Y.config.doc).delegate('click', this.collapse_expand_all, SELECTORS.COLLAPSEEXPAND, this);
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 56);
Y.all(SELECTORS.COLLAPSEEXPAND).each(function(el){
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 2)", 56);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 57);
var ancestor = el.ancestor(SELECTORS.COURSECATEGORYTREE);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 58);
if (ancestor) {
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 59);
NS.update_collapsible_actions(ancestor);
        }
    });
};

/**
 * Toggle the animation of the clicked category node.
 *
 * @method toggle_category_expansion
 * @private
 * @param {EventFacade} e
 */
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 71);
NS.toggle_category_expansion = function(e) {
    // Load the actual dependencies now that we've been called.
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "toggle_category_expansion", 71);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 73);
Y.use('io-base', 'json-parse', 'moodle-core-notification', 'anim', function() {
        // Overload the toggle_category_expansion with the _toggle_category_expansion function to ensure that
        // this function isn't called in the future, and call it for the first time.
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 3)", 73);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 76);
NS.toggle_category_expansion = NS._toggle_category_expansion;
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 77);
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
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 88);
NS.toggle_coursebox_expansion = function(e) {
    // Load the actual dependencies now that we've been called.
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "toggle_coursebox_expansion", 88);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 90);
Y.use('io-base', 'json-parse', 'moodle-core-notification', 'anim', function() {
        // Overload the toggle_coursebox_expansion with the _toggle_coursebox_expansion function to ensure that
        // this function isn't called in the future, and call it for the first time.
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 4)", 90);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 93);
NS.toggle_coursebox_expansion = NS._toggle_coursebox_expansion;
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 94);
NS.toggle_coursebox_expansion(e);
    });

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 97);
e.preventDefault();
};

_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 100);
NS._toggle_coursebox_expansion = function(e) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "_toggle_coursebox_expansion", 100);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 101);
var courseboxnode;

    // Grab the parent category container - this is where the new content will be added.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 104);
courseboxnode = e.target.ancestor(SELECTORS.COURSEBOX, true);
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 105);
e.preventDefault();

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 107);
if (courseboxnode.hasClass(CSS.LOADED)) {
        // We've already loaded this content so we just need to toggle the view of it.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 109);
this.run_expansion(courseboxnode);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 110);
return;
    }

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 113);
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

_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 124);
NS._toggle_category_expansion = function(e) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "_toggle_category_expansion", 124);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 125);
var categorynode,
        categoryid,
        depth;

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 129);
if (e.target.test('a') || e.target.test('img')) {
        // Return early if either an anchor or an image were clicked.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 131);
return;
    }

    // Grab the parent category container - this is where the new content will be added.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 135);
categorynode = e.target.ancestor(SELECTORS.PARENTWITHCHILDREN, true);

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 137);
if (!categorynode.hasClass(CSS.HASCHILDREN)) {
        // Nothing to do here - this category has no children.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 139);
return;
    }

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 142);
if (categorynode.hasClass(CSS.LOADED)) {
        // We've already loaded this content so we just need to toggle the view of it.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 144);
this.run_expansion(categorynode);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 145);
return;
    }

    // We use Data attributes to store the category.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 149);
categoryid = categorynode.getData('categoryid');
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 150);
depth = categorynode.getData('depth');
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 151);
if (typeof categoryid === "undefined" || typeof depth === "undefined") {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 152);
return;
    }

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 155);
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
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 175);
NS._toggle_generic_expansion = function(config) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "_toggle_generic_expansion", 175);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 176);
if (config.spinnerhandle) {
      // Add a spinner to give some feedback to the user.
      _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 178);
spinner = M.util.add_spinner(Y, config.parentnode.one(config.spinnerhandle)).show();
    }

    // Fetch the data.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 182);
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
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 204);
NS.run_expansion = function(categorynode) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "run_expansion", 204);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 205);
var categorychildren = categorynode.one(SELECTORS.CONTENTNODE),
        self = this,
        ancestor = categorynode.ancestor(SELECTORS.COURSECATEGORYTREE);

    // Add our animation to the categorychildren.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 210);
this.add_animation(categorychildren);


    // If we already have the class, remove it before showing otherwise we perform the
    // animation whilst the node is hidden.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 215);
if (categorynode.hasClass(CSS.SECTIONCOLLAPSED)) {
        // To avoid a jump effect, we need to set the height of the children to 0 here before removing the SECTIONCOLLAPSED class.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 217);
categorychildren.setStyle('height', '0');
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 218);
categorynode.removeClass(CSS.SECTIONCOLLAPSED);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 219);
categorychildren.fx.set('reverse', false);
    } else {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 221);
categorychildren.fx.set('reverse', true);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 222);
categorychildren.fx.once('end', function(e, categorynode) {
            _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 5)", 222);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 223);
categorynode.addClass(CSS.SECTIONCOLLAPSED);
        }, this, categorynode);
    }

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 227);
categorychildren.fx.once('end', function(e, categorychildren) {
        // Remove the styles that the animation has set.
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 6)", 227);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 229);
categorychildren.setStyles({
            height: '',
            opacity: ''
        });

        // To avoid memory gobbling, remove the animation. It will be added back if called again.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 235);
this.destroy();
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 236);
self.update_collapsible_actions(ancestor);
    }, categorychildren.fx, categorychildren);

    // Now that everything has been set up, run the animation.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 240);
categorychildren.fx.run();
};

_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 243);
NS.collapse_expand_all = function(e) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "collapse_expand_all", 243);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 244);
var ancestor = e.currentTarget.ancestor(SELECTORS.COURSECATEGORYTREE);
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 245);
if (!ancestor) {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 246);
return;
    }
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 248);
var collapseall = ancestor.one(SELECTORS.COLLAPSEEXPAND);
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 249);
if (collapseall.hasClass(CSS.COLLAPSEALL)) {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 250);
this.collapse_all(ancestor);
    } else {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 252);
this.expand_all(ancestor);
    }
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 254);
this.update_collapsible_actions(ancestor);
};

_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 257);
NS.expand_all = function(ancestor) {
    // We need to expand their children before we expand them to make
    // things easier for adding the animations.
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "expand_all", 257);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 260);
ancestor.all(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)
        .each(function(c) {
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 7)", 261);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 262);
if (c.ancestor(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)) {
            // We can just open this one - it's hidden from view
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 264);
c.removeClass(CSS.SECTIONCOLLAPSED);
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 265);
c.all(SELECTORS.LOADEDTREES).removeClass(CSS.SECTIONCOLLAPSED);
        } else {
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 267);
this.run_expansion(c);
        }
    }, this);
};

_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 272);
NS.collapse_all = function(ancestor) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "collapse_all", 272);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 273);
ancestor.all(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN)
        .each(function(c) {
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 8)", 274);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 275);
if (c.ancestor(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN)) {
            // We can just open this one - it's hidden from view
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 277);
c.addClass(CSS.SECTIONCOLLAPSED);
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 278);
c.all(SELECTORS.LOADEDTREES).addClass(CSS.SECTIONCOLLAPSED);
        } else {
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 280);
this.run_expansion(c);
        }
    }, this);
};

_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 285);
NS.update_collapsible_actions = function(ancestor) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "update_collapsible_actions", 285);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 286);
var foundchildren = false,
        // Grab the anchor for the collapseexpand all link.
        togglelink = ancestor.one(SELECTORS.COLLAPSEEXPAND);

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 290);
if (!togglelink) {
        // We should always have a togglelink but ensure.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 292);
return;
    }

    // Search for any visibly expanded children.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 296);
ancestor.all(SELECTORS.CATEGORYWITHMAXIMISEDLOADEDCHILDREN).each(function(n) {
        // If we can find any collapsed ancestors, skip.
        _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "(anonymous 9)", 296);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 298);
if (n.ancestor(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN)) {
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 299);
return false;
        }
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 301);
foundchildren = true;
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 302);
return true;
    });

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 305);
if (foundchildren) {
        // At least one maximised child found. Show the collapseall.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 307);
togglelink.setHTML(M.util.get_string('collapseall', 'moodle'))
            .addClass(CSS.COLLAPSEALL);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 309);
togglelink.removeClass('hiddenifjs');
    } else {_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 310);
if (ancestor.all(SELECTORS.CATEGORYWITHCOLLAPSEDLOADEDCHILDREN).size()) {
        // No maximised children found but there are collapsed children. Show the expandall.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 312);
togglelink.setHTML(M.util.get_string('expandall', 'moodle'))
            .removeClass(CSS.COLLAPSEALL);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 314);
togglelink.removeClass('hiddenifjs');
    } else {
        // Nothing can be either expanded or collapsed
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 317);
togglelink.addClass('hiddenifjs');
    }}
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
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 331);
NS.process_results = function(tid, response, args) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "process_results", 331);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 332);
var newnode,
        data;
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 334);
try {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 335);
data = Y.JSON.parse(response.responseText);
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 336);
if (data.error) {
            _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 337);
return new M.core.ajaxException(data);
        }
    } catch (e) {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 340);
return new M.core.exception(e);
    }

    // Insert the returned data into a new Node.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 344);
newnode = Y.Node.create(data);

    // Append to the existing child location.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 347);
args.childnode.appendChild(newnode);

    // Now that we have content, we can swap the classes on the toggled container.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 350);
args.parentnode
        .addClass(CSS.LOADED)
        .removeClass(CSS.NOTLOADED);

    // Toggle the open/close status of the node now that it's content has been loaded.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 355);
this.run_expansion(args.parentnode);

    // Remove the spinner now that we've started to show the content.
    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 358);
if (args.spinner) {
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 359);
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
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 370);
NS.add_animation = function(childnode) {
    _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "add_animation", 370);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 371);
if (typeof childnode.fx !== "undefined") {
        // The animation has already been plugged to this node.
        _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 373);
return childnode;
    }

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 376);
childnode.plug(Y.Plugin.NodeFX, {
        from: {
            height: 0,
            opacity: 0
        },
        to: {
            // This sets a dynamic height in case the node content changes.
            height: function(node) {
                // Get expanded height (offsetHeight may be zero).
                _yuitest_coverfunc("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", "height", 383);
_yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 385);
return node.get('scrollHeight');
            },
            opacity: 1
        },
        duration: 0.2
    });

    _yuitest_coverline("build/moodle-course-categoryexpander/moodle-course-categoryexpander.js", 392);
return childnode;
};


}, '@VERSION@', {"requires": ["node"]});
