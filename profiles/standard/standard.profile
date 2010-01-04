<?php
// $Id: standard.profile,v 1.1 2010/01/04 23:08:34 webchick Exp $

/**
 * Implements hook_form_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function standard_form_alter(&$form, $form_state, $form_id) {
  if ($form_id == 'install_configure_form') {
    // Set default for site name field.
    $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
  }
}
