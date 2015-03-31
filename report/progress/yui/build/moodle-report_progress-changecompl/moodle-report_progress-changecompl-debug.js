YUI.add('moodle-report_progress-changecompl', function (Y, NAME) {

/**
 * A module to manage ajax requests.
 *
 * @module moodle-report_progress-changecompl
 */

/**
 * A module to manage ajax requests.
 *
 * @class moodle-report_progress.changecompl
 * @extends Base
 * @constructor
 */
function ChangeCompl() {
    ChangeCompl.superclass.constructor.apply(this, arguments);
}


var CSS = {
    },
    SELECTORS = {
        CHANGECOMPLLINK : '#completion-progress a.changecompl'
    },
    COMPL = {
        COMPLETION_INCOMPLETE : 0,
        COMPLETION_COMPLETE : 1,
        COMPLETION_COMPLETE_PASS : 2,
        COMPLETION_COMPLETE_FAIL : 3
    },
    BODY = Y.one(document.body);

Y.extend(ChangeCompl, Y.Base, {

    /**
     * Initializer.
     * Basic setup and delegations.
     *
     * @method initializer
     */
    initializer: function() {
        Y.delegate('click', this.process_change_completion, BODY, SELECTORS.CHANGECOMPLLINK, this);
    },

    process_change_completion: function(e) {
        e.preventDefault();
        var el = e.currentTarget,
            changecompl = el.getData('changecompl');
    }

}, {
    NAME: 'changeCompl',
    ATTRS: {
    }
});

Y.namespace('M.report_progress.ChangeCompl').init = function(config) {
    return new ChangeCompl(config);
};


}, '@VERSION@', {"requires": ["base", "node"]});
