<?php

/**
 * @file
 * Contains \Drupal\contact\Entity\ContactForm.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\contact\ContactFormInterface;

/**
 * Defines the contact form entity.
 *
 * @ConfigEntityType(
 *   id = "contact_form",
 *   label = @Translation("Contact form"),
 *   handlers = {
 *     "access" = "Drupal\contact\ContactFormAccessControlHandler",
 *     "list_builder" = "Drupal\contact\ContactFormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contact\ContactFormEditForm",
 *       "edit" = "Drupal\contact\ContactFormEditForm",
 *       "delete" = "Drupal\contact\Form\ContactFormDeleteForm"
 *     }
 *   },
 *   config_prefix = "form",
 *   admin_permission = "administer contact forms",
 *   bundle_of = "contact_message",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "entity.contact_form.delete_form",
 *     "edit-form" = "entity.contact_form.edit_form"
 *   }
 * )
 */
class ContactForm extends ConfigEntityBundleBase implements ContactFormInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  public $id;

  /**
   * The form label.
   *
   * @var string
   */
  public $label;

  /**
   * List of recipient email addresses.
   *
   * @var array
   */
  public $recipients = array();

  /**
   * An auto-reply message to send to the message author.
   *
   * @var string
   */
  public $reply = '';

  /**
   * Weight of this form (used for sorting).
   *
   * @var int
   */
  public $weight = 0;

}
