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
