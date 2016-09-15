<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    //$settings->add(new admin_setting_configcheckbox('mediasetting', 'filter_multilang_force_old',
    //    get_string('multilangforceold', 'admin'), 0));

    $settings->add(new admin_setting_configcheckbox('media_flowplayer/mp3', new lang_string('mp3', 'media_flowplayer'),
        new lang_string('configmp3', 'media_flowplayer'), 1));

    $settings->add(new admin_setting_configcheckbox('media_flowplayer/flv', new lang_string('flv', 'media_flowplayer'),
        new lang_string('configflv', 'media_flowplayer'), 1));
}
