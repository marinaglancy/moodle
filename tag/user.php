<?php

require_once('../config.php');
require_once('lib.php');

$action = optional_param('action', '', PARAM_ALPHA);

require_login();

if (empty($CFG->usetags)) {
    print_error('tagdisabled');
}

if (isguestuser()) {
    print_error('noguest');
}

if (!confirm_sesskey()) {
    print_error('sesskey');
}

$usercontext = context_user::instance($USER->id);

switch ($action) {
    case 'addinterest':
        if (!core_tag::is_enabled('user', 'core')) {
            print_error('tagdisabled');
        }
        $tag = required_param('tag', PARAM_TAG);
        core_tag::add_item_tag('user', 'core', $USER->id, $usercontext, $tag);
        $tc = core_tag_area::get_collection('user', 'core');
        redirect(core_tag::get_view_url($tc, $tag));
        break;

    case 'removeinterest':
        if (!core_tag::is_enabled('user', 'core')) {
            print_error('tagdisabled');
        }
        $tag = required_param('tag', PARAM_TAG);
        core_tag::remove_item_tag('user', 'core', $USER->id, $tag);
        $tc = core_tag_area::get_collection('user', 'core');
        redirect(core_tag::get_view_url($tc, $tag));
        break;

    case 'flaginappropriate':
        require_capability('moodle/tag:flag', context_system::instance());
        $id = required_param('id', PARAM_INT);
        $tagobject = core_tag::get($id, '*', MUST_EXIST);
        $tagobject->flag();
        redirect($tagobject->viewurl, get_string('responsiblewillbenotified', 'tag'));
        break;

    default:
        print_error('unknowaction');
        break;
}
