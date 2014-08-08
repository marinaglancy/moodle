<?php

/**
 * This local plugin is being used temporarily to hold install and upgrade
 * scripts that would normally go into core lib/db.  We are not putting them in
 * core yet because we don't want version number conflicts with core upgrades
 * while these gradebook changes are in development.
 */

$plugin->version  = 2014080700;
$plugin->requires = 2013110500;
$plugin->component = 'local_gradebookcoredb';
