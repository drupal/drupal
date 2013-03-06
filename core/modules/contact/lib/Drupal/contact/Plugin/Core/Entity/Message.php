<?php

/**
 * @file
 * Contains Drupal\contact\Plugin\Core\Entity\Message.
 */

namespace Drupal\contact\Plugin\Core\Entity;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Entity;

/**
 * Defines the contact message entity.
 *
 * @Plugin(
 *   id = "contact_message",
 *   label = @Translation("Contact message"),
 *   module = "contact",
 *   form_controller_class = {
 *     "default" = "Drupal\contact\MessageFormController"
 *   },
 *   render_controller_class = "Drupal\contact\MessageRenderController",
 *   entity_keys = {
 *     "bundle" = "category"
 *   },
 *   fieldable = TRUE,
 *   bundle_keys = {
 *     "bundle" = "id"
 *   }
 * )
 */
class Message extends Entity {

  /**
   * The contact category ID of this message.
   *
   * @var string
   */
  public $category;

  /**
   * The sender's name.
   *
   * @var string
   */
  public $name;

  /**
   * The sender's e-mail address.
   *
   * @var string
   */
  public $mail;

  /**
   * The user account object of the message recipient.
   *
   * Only applies to the user contact form. For a site contact form category,
   * multiple recipients can be configured. The existence of a $recipient
   * triggers user contact form specific processing in the contact message form
   * controller.
   *
   * @see Drupal\contact\MessageFormController::form()
   * @see Drupal\contact\MessageFormController::save()
   *
   * @todo Convert user contact form into a locked contact category, and replace
   *   Category::$recipients with the user account's e-mail address upon
   *   Entity::create().
   *
   * @var Drupal\user\Plugin\Core\Entity\User
   */
  public $recipient;

  /**
   * The message subject.
   *
   * @var string
   */
  public $subject;

  /**
   * The message text.
   *
   * @var string
   */
  public $message;

  /**
   * Whether to send a copy of the message to the sender.
   *
   * @var bool
   */
  public $copy;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return NULL;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::bundle().
   */
  public function bundle() {
    return $this->category;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::entityInfo().
   */
  public function entityInfo() {
    // The user contact form is not a category/bundle currently, so it is not
    // fieldable. Prevent EntityFormController from calling into Field Attach
    // functions, since those will throw errors without a bundle name.
    $info = entity_get_info($this->entityType);
    if (isset($this->recipient)) {
      $info['fieldable'] = FALSE;
    }
    return $info;
  }

}
