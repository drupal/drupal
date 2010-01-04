<?php
// $Id: minimal.profile,v 1.1 2010/01/04 23:08:34 webchick Exp $

/**
 * Implements hook_form_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function minimal_form_install_configure_form_alter(&$form, $form_state) {
  $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
}
