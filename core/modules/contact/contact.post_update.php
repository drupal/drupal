<?php

/**
 * @file
 * Post update functions for Contact.
 */

use Drupal\contact\Entity\ContactForm;

/**
 * @addtogroup updates-8.1.x-to-8.2.x
 * @{
 */

/**
 * Initialize 'message' and 'redirect' field values to 'contact_form' entities.
 */
function contact_post_update_add_message_redirect_field_to_contact_form() {
  /** @var \Drupal\contact\ContactFormInterface $contact */
  foreach (ContactForm::loadMultiple() as $contact) {
    $contact
      ->setMessage('Your message has been sent.')
      ->setRedirectPath('')
      ->save();
  }
}

/**
 * @} End of "addtogroup updates-8.1.x-to-8.2.x".
 */
