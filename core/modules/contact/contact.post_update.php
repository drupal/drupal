<?php

/**
 * @file
 * Post update functions for Contact.
 */

/**
 * Implements hook_removed_post_updates().
 */
function contact_removed_post_updates(): array {
  return [
    'contact_post_update_add_message_redirect_field_to_contact_form' => '9.0.0',
    'contact_post_update_set_empty_default_form_to_null' => '11.0.0',
  ];
}
