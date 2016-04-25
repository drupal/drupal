<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Provides a trait for accessing revision logging and ownership information.
 *
 * @ingroup entity_api
 */
trait RevisionLogEntityTrait {

  /**
   * Provides revision-related base field definitions for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of base field definitions for the entity type, keyed by field
   *   name.
   *
   * @see \Drupal\Core\Entity\FieldableEntityInterface::baseFieldDefinitions()
   */
  public static function revisionLogBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['revision_created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision create time'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE);

    $fields['revision_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields['revision_log_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('Briefly describe the changes you have made.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    return $fields;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionCreationTime().
   */
  public function getRevisionCreationTime() {
    return $this->revision_created->value;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionCreationTime().
   */
  public function setRevisionCreationTime($timestamp) {
    $this->revision_created->value = $timestamp;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser().
   */
  public function getRevisionUser() {
    return $this->revision_user->entity;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionUser().
   */
  public function setRevisionUser(UserInterface $account) {
    $this->revision_user->entity = $account;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionUserId().
   */
  public function getRevisionUserId() {
    return $this->revision_user->target_id;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId().
   */
  public function setRevisionUserId($user_id) {
    $this->revision_user->target_id = $user_id;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionLogMessage().
   */
  public function getRevisionLogMessage() {
    return $this->revision_log_message->value;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionLogMessage().
   */
  public function setRevisionLogMessage($revision_log_message) {
    $this->revision_log_message->value = $revision_log_message;
    return $this;
  }

}
