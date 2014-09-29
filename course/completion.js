
M.core_completion = {};

M.core_completion.init = function(Y) {

    var COMPLETION_TRACKING_MANUAL = 1,
        COMPLETION_TRACKING_AUTOMATIC = 2,
        COMPLETION_INCOMPLETE = 0,
        COMPLETION_COMPLETE = 1,
        COMPLETION_COMPLETE_PASS = 2,
        COMPLETION_COMPLETE_FAIL = 3;

    // Check the reload-forcing
    var changeDetector = Y.one('#completion_dynamic_change');
    if (changeDetector.get('value') > 0) {
        changeDetector.set('value', 0);
        window.location.reload();
        return;
    }

    var update_completion = function (cmid, cmode, newstate) {
        // Updates the completion state for a particular module.
        var modulenode = Y.one('#module-' + cmid + '.activity');
        if (!modulenode) {
            return;
        }
        if (cmode == COMPLETION_TRACKING_MANUAL) {
            var imagenode = modulenode.one('form.togglecompletion input[type=image]');
            var moduleinput = modulenode.one('form.togglecompletion input[type=hidden][name=modulename]');
            var altstr, titlestr;
            if (imagenode && moduleinput) {
                var modulename = moduleinput.get('value');
                if (newstate == COMPLETION_INCOMPLETE) {
                    altstr = M.str.completion['completion-alt-manual-n'].replace('{$a}', modulename);
                    titlestr = M.str.completion['completion-title-manual-n'].replace('{$a}', modulename);
                    imagenode.set('src', M.util.image_url('i/completion-manual-n', 'moodle'));
                } else if (newstate == COMPLETION_COMPLETE) {
                    altstr = M.str.completion['completion-alt-manual-y'].replace('{$a}', modulename);
                    titlestr = M.str.completion['completion-title-manual-y'].replace('{$a}', modulename);
                    imagenode.set('src', M.util.image_url('i/completion-manual-y', 'moodle'));
                } else {
                    return;
                }
                imagenode.set('alt', altstr);
                imagenode.set('title', titlestr);
            }
        } else {
            var imgnode = modulenode.one('.autocompletion img');
            var modulename = modulenode.one('.instancename')
            var altstr, titlestr;
            if (imgnode && modulename) {
                imagenode.set('src', M.util.image_url('i/completion-manual-y', 'moodle'));
            }
            imagenode.set('alt', altstr);
            imagenode.set('title', titlestr);
        }
    }

    var handle_success = function(id, o, args) {
        Y.one('#completion_dynamic_change').set('value', 1);

        if (o.responseText != 'OK') {
            alert('An error occurred when attempting to save your tick mark.\n\n('+o.responseText+'.)'); //TODO: localize

        } else {
            var current = args.state.get('value');
            var modulename = args.modulename.get('value');
            if (current == 1) {
                var altstr = M.str.completion['completion-alt-manual-y'].replace('{$a}', modulename);
                var titlestr = M.str.completion['completion-title-manual-y'].replace('{$a}', modulename);
                args.state.set('value', 0);
                args.image.set('src', M.util.image_url('i/completion-manual-y', 'moodle'));
                args.image.set('alt', altstr);
                args.image.set('title', titlestr);
            } else {
                var altstr = M.str.completion['completion-alt-manual-n'].replace('{$a}', modulename);
                var titlestr = M.str.completion['completion-title-manual-n'].replace('{$a}', modulename);
                args.state.set('value', 1);
                args.image.set('src', M.util.image_url('i/completion-manual-n', 'moodle'));
                args.image.set('alt', altstr);
                args.image.set('title', titlestr);
            }
        }

        args.ajax.remove();
    };

    var handle_failure = function(id, o, args) {
        alert('An error occurred when attempting to save your tick mark.\n\n('+o.responseText+'.)'); //TODO: localize
        args.ajax.remove();
    };

    var toggle = function(e) {
        e.preventDefault();

        var form = e.target;
        var cmid = 0;
        var completionstate = 0;
        var state = null;
        var image = null;
        var modulename = null;

        var inputs = Y.Node.getDOMNode(form).getElementsByTagName('input');
        for (var i=0; i<inputs.length; i++) {
            switch (inputs[i].name) {
                 case 'id':
                     cmid = inputs[i].value;
                     break;
                  case 'completionstate':
                     completionstate = inputs[i].value;
                     state = Y.one(inputs[i]);
                     break;
                  case 'modulename':
                     modulename = Y.one(inputs[i]);
                     break;
            }
            if (inputs[i].type == 'image') {
                image = Y.one(inputs[i]);
            }
        }

        // start spinning the ajax indicator
        var ajax = Y.Node.create('<div class="ajaxworking" />');
        form.append(ajax);

        var cfg = {
            method: "POST",
            data: 'id='+cmid+'&completionstate='+completionstate+'&fromajax=1&sesskey='+M.cfg.sesskey,
            on: {
                success: handle_success,
                failure: handle_failure
            },
            arguments: {state: state, image: image, ajax: ajax, modulename: modulename}
        };

        Y.use('io-base', function(Y) {
            Y.io(M.cfg.wwwroot+'/course/togglecompletion.php', cfg);
        });
    };

    // register submit handlers on manual tick completion forms
    Y.all('form.togglecompletion').each(function(form) {
        if (!form.hasClass('preventjs')) {
            Y.on('submit', toggle, form);
        }
    });

    // hide the help if there are no completion toggles or icons
    var help = Y.one('#completionprogressid');
    if (help && !(Y.one('form.togglecompletion') || Y.one('.autocompletion'))) {
        help.setStyle('display', 'none');
    }
};


