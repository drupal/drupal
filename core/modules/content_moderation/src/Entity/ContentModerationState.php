<?php

namespace Drupal\content_moderation\Entity;

use Drupal\content_moderation\ContentModerationStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\user\UserInterface;

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
 *   },
 *   base_table = "content_moderation_state",
 *   revision_table = "content_moderation_state_revision",
 *   data_table = "content_moderation_state_field_data",
 *   revision_data_table = "content_moderation_state_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class ContentModerationState extends ContentEntityBase implements ContentModerationStateInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The username of the entity creator.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\content_moderation\Entity\ContentModerationState::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['moderation_state'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of the referenced content.'))
      ->setSetting('target_type', 'moderation_state')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->addConstraint('ModerationState', []);

    $fields['content_entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content entity type ID'))
      ->setDescription(t('The ID of the content entity type this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['content_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity ID'))
      ->setDescription(t('The ID of the content entity this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    // @todo https://www.drupal.org/node/2779931 Add constraint that enforces
    //   unique content_entity_type_id, content_entity_id and
    //   content_entity_revision_id.

    $fields['content_entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity revision ID'))
      ->setDescription(t('The revision ID of the content entity this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
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
   * Default value callback for the 'uid' base field definition.
   *
   * @see \Drupal\content_moderation\Entity\ContentModerationState::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
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
    $related_entity->moderation_state->target_id = $this->moderation_state->target_id;
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

}
