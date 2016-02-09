<?php


$callbacks = array(
    array(
        'hook' => '\core\hook\inplace_editable',
        'callback' => '\core_cohort\output\cohortname::update',
    ),
    array(
        'hook' => '\core\hook\inplace_editable',
        'callback' => '\core_cohort\output\cohortidnumber::update',
    ),
);