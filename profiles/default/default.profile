<?php
// $Id: default.profile,v 1.65 2010/01/03 06:58:52 dries Exp $

/**
 * Implements hook_form_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function default_form_alter(&$form, $form_state, $form_id) {
  if ($form_id == 'install_configure_form') {
    // Set default for site name field.
    $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
  }
}
