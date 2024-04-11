<?php

/**
 * @file
 * Post update functions for Contact.
 */

/**
 * Implements hook_removed_post_updates().
 */
function contact_removed_post_updates() {
  return [
    'contact_post_update_add_message_redirect_field_to_contact_form' => '9.0.0',
  ];
}

/**
 * Converts empty `default_form` in settings to NULL.
 */
function contact_post_update_set_empty_default_form_to_null(): void {
  $config = \Drupal::configFactory()->getEditable('contact.settings');
  // 'default_form' in 'contact.settings' config must be stored as NULL if it
  // is empty.
  if ($config->get('default_form') === '') {
    $config->set('default_form', NULL)->save();
  }
}
