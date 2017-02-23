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
    $fields[static::getRevisionMetadataKey($entity_type, 'revision_created')] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision create time'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE);

    $fields[static::getRevisionMetadataKey($entity_type, 'revision_user')] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields[static::getRevisionMetadataKey($entity_type, 'revision_log_message')] = BaseFieldDefinition::create('string_long')
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
    return $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_created')}->value;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionCreationTime().
   */
  public function setRevisionCreationTime($timestamp) {
    $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_created')}->value = $timestamp;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser().
   */
  public function getRevisionUser() {
    return $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_user')}->entity;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionUser().
   */
  public function setRevisionUser(UserInterface $account) {
    $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_user')}->entity = $account;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionUserId().
   */
  public function getRevisionUserId() {
    return $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_user')}->target_id;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId().
   */
  public function setRevisionUserId($user_id) {
    $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_user')}->target_id = $user_id;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::getRevisionLogMessage().
   */
  public function getRevisionLogMessage() {
    return $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_log_message')}->value;
  }

  /**
   * Implements \Drupal\Core\Entity\RevisionLogInterface::setRevisionLogMessage().
   */
  public function setRevisionLogMessage($revision_log_message) {
    $this->{static::getRevisionMetadataKey($this->getEntityType(), 'revision_log_message')}->value = $revision_log_message;
    return $this;
  }

  /**
   * Gets the name of a revision metadata field.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   A content entity type definition.
   * @param string $key
   *   The revision metadata key to get, must be one of 'revision_created',
   *   'revision_user' or 'revision_log_message'.
   *
   * @return string
   *   The name of the field for the specified $key.
   */
  protected static function getRevisionMetadataKey(EntityTypeInterface $entity_type, $key) {
    // We need to prevent ContentEntityType::getRevisionMetadataKey() from
    // providing fallback as that requires fetching the entity type's field
    // definition leading to an infinite recursion.
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $revision_metadata_keys = $entity_type->getRevisionMetadataKeys(FALSE) + [
      'revision_created' => 'revision_created',
      'revision_user' => 'revision_user',
      'revision_log_message' => 'revision_log_message',
    ];

    return $revision_metadata_keys[$key];
  }

}
