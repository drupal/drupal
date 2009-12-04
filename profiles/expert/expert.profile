<?php
// $Id: expert.profile,v 1.15 2009/12/04 16:49:48 dries Exp $

/**
 * Implements hook_form_alter().
 *
 * Allows the profile to alter the site-configuration form. This is
 * called through custom invocation, so $form_state is not populated.
 */
function expert_form_install_configure_form_alter(&$form) {
  $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
}
