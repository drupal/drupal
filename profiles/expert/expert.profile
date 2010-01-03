<?php
// $Id: expert.profile,v 1.16 2010/01/03 06:58:52 dries Exp $

/**
 * Implements hook_form_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function expert_form_install_configure_form_alter(&$form, $form_state) {
  $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
}
