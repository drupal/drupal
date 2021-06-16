<?php

namespace Drupal\contact\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\contact\MessageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the contact message entity.
 *
 * @ContentEntityType(
 *   id = "contact_message",
 *   label = @Translation("Contact message"),
 *   label_collection = @Translation("Contact messages"),
 *   label_singular = @Translation("contact message"),
 *   label_plural = @Translation("contact messages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count contact message",
 *     plural = "@count contact messages",
 *   ),
 *   bundle_label = @Translation("Contact form"),
 *   handlers = {
 *     "access" = "Drupal\contact\ContactMessageAccessControlHandler",
 *     "storage" = "Drupal\Core\Entity\ContentEntityNullStorage",
 *     "view_builder" = "Drupal\contact\MessageViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\contact\MessageForm"
 *     }
 *   },
 *   admin_permission = "administer contact forms",
 *   entity_keys = {
 *     "bundle" = "contact_form",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   bundle_entity_type = "contact_form",
 *   field_ui_base_route = "entity.contact_form.edit_form",
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
  public function getContactForm() {
    return $this->get('contact_form')->entity;
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
    return (bool) $this->get('copy')->value;
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
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['contact_form']->setLabel(t('Form ID'))
      ->setDescription(t('The ID of the associated form.'));

    $fields['uuid']->setDescription(t('The message UUID.'));

    $fields['langcode']->setDescription(t('The message language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t("The sender's name"))
      ->setDescription(t('The name of the person that is sending the contact message.'));

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t("The sender's email"))
      ->setDescription(t('The email of the person that is sending the contact message.'));

    // The subject of the contact message.
    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // The text of the contact message.
    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['copy'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Copy'))
      ->setDescription(t('Whether to send a copy of the message to the sender.'));

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient ID'))
      ->setDescription(t('The ID of the recipient user for personal contact messages.'))
      ->setSetting('target_type', 'user');

    return $fields;
  }

}
