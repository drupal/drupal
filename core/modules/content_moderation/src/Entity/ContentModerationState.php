<?php

namespace Drupal\content_moderation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Content moderation state entity.
 *
 * @ContentEntityType(
 *   id = "content_moderation_state",
 *   label = @Translation("Content moderation state"),
 *   label_singular = @Translation("content moderation state"),
 *   label_plural = @Translation("content moderation states"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content moderation state",
 *     plural = "@count content moderation states"
 *   ),
 *   handlers = {
 *     "storage_schema" = "Drupal\content_moderation\ContentModerationStateStorageSchema",
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *     "access" = "Drupal\content_moderation\ContentModerationStateAccessControlHandler",
 *   },
 *   base_table = "content_moderation_state",
 *   revision_table = "content_moderation_state_revision",
 *   data_table = "content_moderation_state_field_data",
 *   revision_data_table = "content_moderation_state_field_revision",
 *   translatable = TRUE,
 *   internal = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *   }
 * )
 *
 * @internal
 *   This entity is marked internal because it should not be used directly to
 *   alter the moderation state of an entity. Instead, the computed
 *   moderation_state field should be set on the entity directly.
 */
class ContentModerationState extends ContentEntityBase implements ContentModerationStateInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['uid']
      ->setLabel(t('User'))
      ->setDescription(t('The username of the entity creator.'))
      ->setRevisionable(TRUE);

    $fields['workflow'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Workflow'))
      ->setDescription(t('The workflow the moderation state is in.'))
      ->setSetting('target_type', 'workflow')
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['moderation_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of the referenced content.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['content_entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content entity type ID'))
      ->setDescription(t('The ID of the content entity type this moderation state is for.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setRevisionable(TRUE);

    $fields['content_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity ID'))
      ->setDescription(t('The ID of the content entity this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['content_entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity revision ID'))
      ->setDescription(t('The revision ID of the content entity this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * Creates or updates an entity's moderation state whilst saving that entity.
   *
   * @param \Drupal\content_moderation\Entity\ContentModerationState $content_moderation_state
   *   The content moderation entity content entity to create or save.
   *
   * @internal
   *   This method should only be called as a result of saving the related
   *   content entity.
   */
  public static function updateOrCreateFromEntity(ContentModerationState $content_moderation_state) {
    $content_moderation_state->realSave();
  }

  /**
   * Loads a content moderation state entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A moderated entity object.
   *
   * @return \Drupal\content_moderation\Entity\ContentModerationStateInterface|null
   *   The related content moderation state or NULL if none could be found.
   *
   * @internal
   *   This method should only be called by code directly handling the
   *   ContentModerationState entity objects.
   */
  public static function loadFromModeratedEntity(EntityInterface $entity) {
    $content_moderation_state = NULL;
    $moderation_info = \Drupal::service('content_moderation.moderation_information');

    if ($moderation_info->isModeratedEntity($entity)) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $storage = \Drupal::entityTypeManager()->getStorage('content_moderation_state');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('content_entity_type_id', $entity->getEntityTypeId())
        ->condition('content_entity_id', $entity->id())
        ->condition('workflow', $moderation_info->getWorkflowForEntity($entity)->id())
        ->condition('content_entity_revision_id', $entity->getLoadedRevisionId())
        ->allRevisions()
        ->execute();

      if ($ids) {
        /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface $content_moderation_state */
        $content_moderation_state = $storage->loadRevision(key($ids));
      }
    }

    return $content_moderation_state;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $related_entity = \Drupal::entityTypeManager()
      ->getStorage($this->content_entity_type_id->value)
      ->loadRevision($this->content_entity_revision_id->value);
    if ($related_entity instanceof TranslatableInterface) {
      $related_entity = $related_entity->getTranslation($this->activeLangcode);
    }
    $related_entity->moderation_state = $this->moderation_state;
    return $related_entity->save();
  }

  /**
   * Saves an entity permanently.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  protected function realSave() {
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToSkipFromTranslationChangesCheck() {
    $field_names = parent::getFieldsToSkipFromTranslationChangesCheck();
    // We need to skip the parent entity revision ID, since that will always
    // change on every save, otherwise every translation would be marked as
    // affected regardless of actual changes.
    $field_names[] = 'content_entity_revision_id';
    return $field_names;
  }

}
