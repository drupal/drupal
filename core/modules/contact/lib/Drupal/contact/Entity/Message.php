<?php

/**
 * @file
 * Contains Drupal\contact\Entity\Message.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityNG;
use Drupal\contact\MessageInterface;

/**
 * Defines the contact message entity.
 *
 * @EntityType(
 *   id = "contact_message",
 *   label = @Translation("Contact message"),
 *   module = "contact",
 *   controllers = {
 *     "storage" = "Drupal\contact\MessageStorageController",
 *     "render" = "Drupal\contact\MessageRenderController",
 *     "form" = {
 *       "default" = "Drupal\contact\MessageFormController"
 *     }
 *   },
 *   entity_keys = {
 *     "bundle" = "category"
 *   },
 *   route_base_path = "admin/structure/contact/manage/{bundle}",
 *   fieldable = TRUE,
 *   bundle_keys = {
 *     "bundle" = "id"
 *   }
 * )
 */
class Message extends EntityNG implements MessageInterface {

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isPersonal() {
    return $this->bundle() == 'personal';
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return $this->get('category')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderName($sender_name) {
    $this->set('name', $sender_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderMail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderMail($sender_mail) {
    $this->set('mail', $sender_mail);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->set('message', $message);
  }

  /**
   * {@inheritdoc}
   */
  public function copySender() {
    return (bool)$this->get('copy')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCopySender($inform) {
    $this->set('copy', (bool) $inform);
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonalRecipient() {
    if ($this->isPersonal()) {
      return $this->get('recipient')->entity;
    }
  }

}
