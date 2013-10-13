<?php

/**
 * @file
 * Contains Drupal\contact\Entity\Message.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\contact\MessageInterface;

/**
 * Defines the contact message entity.
 *
 * @EntityType(
 *   id = "contact_message",
 *   label = @Translation("Contact message"),
 *   module = "contact",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\FieldableDatabaseStorageController",
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
class Message extends ContentEntityBase implements MessageInterface {

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields['category'] = array(
      'label' => t('Category ID'),
      'description' => t('The ID of the associated category.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'contact_category'),
      'required' => TRUE,
    );
    $fields['name'] = array(
      'label' => t("The sender's name"),
      'description' => t('The name of the person that is sending the contact message.'),
      'type' => 'string_field',
    );
    $fields['mail'] = array(
      'label' => t("The sender's e-mail"),
      'description' => t('The e-mail of the person that is sending the contact message.'),
      'type' => 'email_field',
    );
    $fields['subject'] = array(
      'label' => t('The message subject'),
      'description' => t('The subject of the contact message.'),
      'type' => 'string_field',
    );
    $fields['message'] = array(
      'label' => t('The message text'),
      'description' => t('The text of the contact message.'),
      'type' => 'string_field',
    );
    $fields['copy'] = array(
      'label' => t('Copy'),
      'description' => t('Whether to send a copy of the message to the sender.'),
      'type' => 'boolean_field',
    );
    $fields['recipient'] = array(
      'label' => t('Recipient ID'),
      'description' => t('The ID of the recipient user for personal contact messages.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    return $fields;
  }

}
