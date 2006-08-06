<?php
// $Id: default.profile,v 1.3 2006/08/06 23:00:42 drumm Exp $

/**
 * Return an array of the modules to be enabled when this profile is installed.
 *
 * @return
 *  An array of modules to be enabled.
 */
function default_profile_modules() {
  return array('block', 'comment', 'filter', 'help', 'menu', 'node', 'system', 'taxonomy', 'user', 'watchdog');
}

/**
 * Return a description of the profile.
 */
function default_profile_details() {
  return array(
    'name' => 'Drupal',
    'description' => 'Select this profile to enable some basic Drupal functionality and the default theme.'
  );
}
