<?php

/**
 * @file
 * Contains Drupal\contact\Entity\Message.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\contact\MessageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * Defines the contact message entity.
 *
 * @ContentEntityType(
 *   id = "contact_message",
 *   label = @Translation("Contact message"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\ContentEntityNullStorage",
 *     "view_builder" = "Drupal\contact\MessageViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\contact\MessageForm"
 *     }
 *   },
 *   entity_keys = {
 *     "bundle" = "category",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "contact_category",
 *   fieldable = TRUE,
 *   links = {
 *     "admin-form" = "entity.contact_category.edit_form"
 *   }
 * )
 */
class Message extends ContentEntityBase implements MessageInterface {

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['category'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Category ID'))
      ->setDescription(t('The ID of the associated category.'))
      ->setSetting('target_type', 'contact_category')
      ->setRequired(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The message UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The comment language code.'));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t("The sender's name"))
      ->setDescription(t('The name of the person that is sending the contact message.'));

    $fields['mail'] = FieldDefinition::create('email')
      ->setLabel(t("The sender's email"))
      ->setDescription(t('The email of the person that is sending the contact message.'));

    // The subject of the contact message.
    $fields['subject'] = FieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    // The text of the contact message.
    $fields['message'] = FieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => array(
          'rows' => 12,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['copy'] = FieldDefinition::create('boolean')
      ->setLabel(t('Copy'))
      ->setDescription(t('Whether to send a copy of the message to the sender.'));

    $fields['recipient'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient ID'))
      ->setDescription(t('The ID of the recipient user for personal contact messages.'))
      ->setSetting('target_type', 'user');

    return $fields;
  }

}
