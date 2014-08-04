<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function standard_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Pre-populate the site name with the server name.
  $form['site_information']['site_name']['#default_value'] = \Drupal::request()->server->get('SERVER_NAME');
}
