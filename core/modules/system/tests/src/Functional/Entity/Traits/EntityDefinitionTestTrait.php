<?php

namespace Drupal\Tests\system\Functional\Entity\Traits;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\FieldStorageDefinition;

/**
 * Provides some test methods used to update existing entity definitions.
 */
trait EntityDefinitionTestTrait {

  /**
   * Applies all the detected valid changes.
   *
   * Use this with care, as it will apply updates for any module, which will
   * lead to unpredictable results.
   *
   * @param string $entity_type_id
   *   (optional) Applies changes only for the specified entity type ID.
   *   Defaults to NULL.
   */
  protected function applyEntityUpdates($entity_type_id = NULL) {
    $complete_change_list = \Drupal::entityDefinitionUpdateManager()->getChangeList();
    if ($complete_change_list) {
      // In case there are changes, explicitly invalidate caches.
      \Drupal::entityTypeManager()->clearCachedDefinitions();
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    }

    if ($entity_type_id) {
      $complete_change_list = array_intersect_key($complete_change_list, [$entity_type_id => TRUE]);
    }

    foreach ($complete_change_list as $entity_type_id => $change_list) {
      // Process entity type definition changes before storage definitions ones
      // this is necessary when you change an entity type from non-revisionable
      // to revisionable and at the same time add revisionable fields to the
      // entity type.
      if (!empty($change_list['entity_type'])) {
        $this->doEntityUpdate($change_list['entity_type'], $entity_type_id);
      }

      // Process field storage definition changes.
      if (!empty($change_list['field_storage_definitions'])) {
        $storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
        $original_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions($entity_type_id);

        foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
          $storage_definition = $storage_definitions[$field_name] ?? NULL;
          $original_storage_definition = $original_storage_definitions[$field_name] ?? NULL;
          $this->doFieldUpdate($change, $storage_definition, $original_storage_definition);
        }
      }
    }
  }

  /**
   * Performs an entity type definition update.
   *
   * @param string $op
   *   The operation to perform, either static::DEFINITION_CREATED or
   *   static::DEFINITION_UPDATED.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function doEntityUpdate($op, $entity_type_id) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
    switch ($op) {
      case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
        \Drupal::service('entity_type.listener')->onEntityTypeCreate($entity_type);
        break;

      case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
        $original = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition($entity_type_id);
        $original_field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions($entity_type_id);

        \Drupal::service('entity_type.listener')->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions);
        break;
    }
  }

  /**
   * Performs a field storage definition update.
   *
   * @param string $op
   *   The operation to perform, possible values are static::DEFINITION_CREATED,
   *   static::DEFINITION_UPDATED or static::DEFINITION_DELETED.
   * @param array|null $storage_definition
   *   The new field storage definition.
   * @param array|null $original_storage_definition
   *   The original field storage definition.
   */
  protected function doFieldUpdate($op, $storage_definition = NULL, $original_storage_definition = NULL) {
    switch ($op) {
      case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
        \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definition);
        break;

      case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
        \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionUpdate($storage_definition, $original_storage_definition);
        break;

      case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
        \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionDelete($original_storage_definition);
        break;
    }
  }

  /**
   * Enables a new entity type definition.
   */
  protected function enableNewEntityType() {
    $this->state->set('entity_test_new', TRUE);
    $this->applyEntityUpdates('entity_test_new');
  }

  /**
   * Resets the entity type definition.
   */
  protected function resetEntityType() {
    $updated_entity_type = $this->getUpdatedEntityTypeDefinition(FALSE, FALSE);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(FALSE, FALSE);
    $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
  }

  /**
   * Updates the 'entity_test_update' entity type to revisionable.
   *
   * @param bool $perform_update
   *   (optional) Whether the change should be performed by the entity
   *   definition update manager.
   */
  protected function updateEntityTypeToRevisionable($perform_update = FALSE) {
    $translatable = $this->entityDefinitionUpdateManager->getEntityType('entity_test_update')->isTranslatable();

    $updated_entity_type = $this->getUpdatedEntityTypeDefinition(TRUE, $translatable);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(TRUE, $translatable);

    if ($perform_update) {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
    }
  }

  /**
   * Updates the 'entity_test_update' entity type not revisionable.
   *
   * @param bool $perform_update
   *   (optional) Whether the change should be performed by the entity
   *   definition update manager.
   */
  protected function updateEntityTypeToNotRevisionable($perform_update = FALSE) {
    $translatable = $this->entityDefinitionUpdateManager->getEntityType('entity_test_update')->isTranslatable();

    $updated_entity_type = $this->getUpdatedEntityTypeDefinition(FALSE, $translatable);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(FALSE, $translatable);

    if ($perform_update) {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
    }
  }

  /**
   * Updates the 'entity_test_update' entity type to translatable.
   *
   * @param bool $perform_update
   *   (optional) Whether the change should be performed by the entity
   *   definition update manager.
   */
  protected function updateEntityTypeToTranslatable($perform_update = FALSE) {
    $revisionable = $this->entityDefinitionUpdateManager->getEntityType('entity_test_update')->isRevisionable();

    $updated_entity_type = $this->getUpdatedEntityTypeDefinition($revisionable, TRUE);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions($revisionable, TRUE);

    if ($perform_update) {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
    }
  }

  /**
   * Updates the 'entity_test_update' entity type to not translatable.
   *
   * @param bool $perform_update
   *   (optional) Whether the change should be performed by the entity
   *   definition update manager.
   */
  protected function updateEntityTypeToNotTranslatable($perform_update = FALSE) {
    $revisionable = $this->entityDefinitionUpdateManager->getEntityType('entity_test_update')->isRevisionable();

    $updated_entity_type = $this->getUpdatedEntityTypeDefinition($revisionable, FALSE);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions($revisionable, FALSE);

    if ($perform_update) {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
    }
  }

  /**
   * Updates the 'entity_test_update' entity type to revisionable and
   * translatable.
   *
   * @param bool $perform_update
   *   (optional) Whether the change should be performed by the entity
   *   definition update manager.
   */
  protected function updateEntityTypeToRevisionableAndTranslatable($perform_update = FALSE) {
    $updated_entity_type = $this->getUpdatedEntityTypeDefinition(TRUE, TRUE);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(TRUE, TRUE);

    if ($perform_update) {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
    }
  }

  /**
   * Adds a new base field to the 'entity_test_update' entity type.
   *
   * @param string $type
   *   (optional) The field type for the new field. Defaults to 'string'.
   * @param string $entity_type_id
   *   (optional) The entity type ID the base field should be attached to.
   *   Defaults to 'entity_test_update'.
   * @param bool $is_revisionable
   *   (optional) If the base field should be revisionable or not. Defaults to
   *   FALSE.
   * @param bool $set_label
   *   (optional) If the base field should have a label or not. Defaults to
   *   TRUE.
   * @param bool $is_translatable
   *   (optional) If the base field should be translatable or not. Defaults to
   *   FALSE.
   */
  protected function addBaseField($type = 'string', $entity_type_id = 'entity_test_update', $is_revisionable = FALSE, $set_label = TRUE, $is_translatable = FALSE) {
    $definitions['new_base_field'] = BaseFieldDefinition::create($type)
      ->setName('new_base_field')
      ->setRevisionable($is_revisionable)
      ->setTranslatable($is_translatable);

    if ($set_label) {
      $definitions['new_base_field']->setLabel(t('A new base field'));
    }

    $this->state->set($entity_type_id . '.additional_base_field_definitions', $definitions);
  }

  /**
   * Adds a long-named base field to the 'entity_test_update' entity type.
   */
  protected function addLongNameBaseField() {
    $key = 'entity_test_update.additional_base_field_definitions';
    $definitions = $this->state->get($key, []);
    $definitions['new_long_named_entity_reference_base_field'] = BaseFieldDefinition::create('entity_reference')
      ->setName('new_long_named_entity_reference_base_field')
      ->setLabel(t('A new long-named base field'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');
    $this->state->set($key, $definitions);
  }

  /**
   * Adds a new revisionable base field to the 'entity_test_update' entity type.
   *
   * @param string $type
   *   (optional) The field type for the new field. Defaults to 'string'.
   */
  protected function addRevisionableBaseField($type = 'string') {
    $definitions['new_base_field'] = BaseFieldDefinition::create($type)
      ->setName('new_base_field')
      ->setLabel(t('A new revisionable base field'))
      ->setRevisionable(TRUE);
    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);
  }

  /**
   * Modifies the new base field from 'string' to 'text'.
   */
  protected function modifyBaseField() {
    $this->addBaseField('text');
  }

  /**
   * Promotes a field to an entity key.
   */
  protected function makeBaseFieldEntityKey() {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_update');
    $entity_keys = $entity_type->getKeys();
    $entity_keys['new_base_field'] = 'new_base_field';
    $entity_type->set('entity_keys', $entity_keys);
    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Removes the new base field from the 'entity_test_update' entity type.
   *
   * @param string $entity_type_id
   *   (optional) The entity type ID the base field should be attached to.
   */
  protected function removeBaseField($entity_type_id = 'entity_test_update') {
    $this->state->delete($entity_type_id . '.additional_base_field_definitions');
  }

  /**
   * Adds a single-field index to the base field.
   */
  protected function addBaseFieldIndex() {
    $this->state->set('entity_test_update.additional_field_index.entity_test_update.new_base_field', TRUE);
  }

  /**
   * Removes the index added in addBaseFieldIndex().
   */
  protected function removeBaseFieldIndex() {
    $this->state->delete('entity_test_update.additional_field_index.entity_test_update.new_base_field');
  }

  /**
   * Adds a new bundle field to the 'entity_test_update' entity type.
   *
   * @param string $type
   *   (optional) The field type for the new field. Defaults to 'string'.
   * @param bool $revisionable
   *   (optional) Whether the field should be revisionable. Defaults to FALSE.
   * @param bool $translatable
   *   (optional) Whether the field should be translatable. Defaults to FALSE.
   */
  protected function addBundleField($type = 'string', $revisionable = FALSE, $translatable = FALSE) {
    $definitions['new_bundle_field'] = FieldStorageDefinition::create($type)
      ->setName('new_bundle_field')
      ->setLabel(t('A new bundle field'))
      ->setTargetEntityTypeId('entity_test_update')
      ->setRevisionable($revisionable)
      ->setTranslatable($translatable);
    $this->state->set('entity_test_update.additional_field_storage_definitions', $definitions);
    $this->state->set('entity_test_update.additional_bundle_field_definitions.test_bundle', $definitions);
  }

  /**
   * Modifies the new bundle field from 'string' to 'text'.
   */
  protected function modifyBundleField() {
    $this->addBundleField('text');
  }

  /**
   * Removes the new bundle field from the 'entity_test_update' entity type.
   */
  protected function removeBundleField() {
    $this->state->delete('entity_test_update.additional_field_storage_definitions');
    $this->state->delete('entity_test_update.additional_bundle_field_definitions.test_bundle');
  }

  /**
   * Adds an index to the 'entity_test_update' entity type's base table.
   *
   * @see \Drupal\entity_test\EntityTestStorageSchema::getEntitySchema()
   */
  protected function addEntityIndex() {
    $indexes = [
      'entity_test_update__new_index' => ['name', 'test_single_property'],
    ];
    $this->state->set('entity_test_update.additional_entity_indexes', $indexes);
  }

  /**
   * Removes the index added in addEntityIndex().
   */
  protected function removeEntityIndex() {
    $this->state->delete('entity_test_update.additional_entity_indexes');
  }

  /**
   * Renames the base table to 'entity_test_update_new'.
   */
  protected function renameBaseTable() {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_update');

    $entity_type->set('base_table', 'entity_test_update_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Renames the data table to 'entity_test_update_data_new'.
   */
  protected function renameDataTable() {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_update');

    $entity_type->set('data_table', 'entity_test_update_data_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Renames the revision table to 'entity_test_update_revision_new'.
   */
  protected function renameRevisionBaseTable() {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_update');

    $entity_type->set('revision_table', 'entity_test_update_revision_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Renames the revision data table to 'entity_test_update_revision_data_new'.
   */
  protected function renameRevisionDataTable() {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_update');

    $entity_type->set('revision_data_table', 'entity_test_update_revision_data_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Removes the entity type.
   */
  protected function deleteEntityType() {
    $this->state->set('entity_test_update.entity_type', 'null');
  }

  /**
   * Returns an entity type definition, possibly updated to be rev or mul.
   *
   * @param bool $revisionable
   *   (optional) Whether the entity type should be revisionable or not.
   *   Defaults to FALSE.
   * @param bool $translatable
   *   (optional) Whether the entity type should be translatable or not.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   An entity type definition.
   */
  protected function getUpdatedEntityTypeDefinition($revisionable = FALSE, $translatable = FALSE) {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_update');

    if ($revisionable) {
      $keys = $entity_type->getKeys();
      $keys['revision'] = 'revision_id';
      $entity_type->set('entity_keys', $keys);
      $entity_type->set('revision_table', 'entity_test_update_revision');
    }
    else {
      $keys = $entity_type->getKeys();
      $keys['revision'] = '';
      $entity_type->set('entity_keys', $keys);
      $entity_type->set('revision_table', NULL);
    }

    if ($translatable) {
      $entity_type->set('translatable', TRUE);
      $entity_type->set('data_table', 'entity_test_update_data');
    }
    else {
      $entity_type->set('translatable', FALSE);
      $entity_type->set('data_table', NULL);
    }

    if ($revisionable && $translatable) {
      $entity_type->set('revision_data_table', 'entity_test_update_revision_data');
    }
    else {
      $entity_type->set('revision_data_table', NULL);
    }

    $this->state->set('entity_test_update.entity_type', $entity_type);

    $this->container->get('entity_type.manager')->clearCachedDefinitions();
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    return $entity_type;
  }

  /**
   * Returns the required rev / mul field definitions for an entity type.
   *
   * @param bool $revisionable
   *   (optional) Whether the entity type should be revisionable or not.
   *   Defaults to FALSE.
   * @param bool $translatable
   *   (optional) Whether the entity type should be translatable or not.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   An array of field storage definition objects.
   */
  protected function getUpdatedFieldStorageDefinitions($revisionable = FALSE, $translatable = FALSE) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_storage_definitions */
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');

    if ($revisionable) {
      // The 'langcode' is already available for the 'entity_test_update' entity
      // type because it has the 'langcode' entity key defined.
      $field_storage_definitions['langcode']->setRevisionable(TRUE);

      $field_storage_definitions['revision_id'] = BaseFieldDefinition::create('integer')
        ->setName('revision_id')
        ->setTargetEntityTypeId('entity_test_update')
        ->setTargetBundle(NULL)
        ->setLabel(new TranslatableMarkup('Revision ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);

      $field_storage_definitions['revision_default'] = BaseFieldDefinition::create('boolean')
        ->setName('revision_default')
        ->setTargetEntityTypeId('entity_test_update')
        ->setTargetBundle(NULL)
        ->setLabel(new TranslatableMarkup('Default revision'))
        ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
        ->setStorageRequired(TRUE)
        ->setInternal(TRUE)
        ->setTranslatable(FALSE)
        ->setRevisionable(TRUE);
    }

    if ($translatable) {
      // The 'langcode' is already available for the 'entity_test_update' entity
      // type because it has the 'langcode' entity key defined.
      $field_storage_definitions['langcode']->setTranslatable(TRUE);

      $field_storage_definitions['default_langcode'] = BaseFieldDefinition::create('boolean')
        ->setName('default_langcode')
        ->setTargetEntityTypeId('entity_test_update')
        ->setTargetBundle(NULL)
        ->setLabel(new TranslatableMarkup('Default translation'))
        ->setDescription(new TranslatableMarkup('A flag indicating whether this is the default translation.'))
        ->setTranslatable(TRUE)
        ->setRevisionable(TRUE)
        ->setDefaultValue(TRUE);
    }

    if ($revisionable && $translatable) {
      $field_storage_definitions['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
        ->setName('revision_translation_affected')
        ->setTargetEntityTypeId('entity_test_update')
        ->setTargetBundle(NULL)
        ->setLabel(new TranslatableMarkup('Revision translation affected'))
        ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
        ->setReadOnly(TRUE)
        ->setRevisionable(TRUE)
        ->setTranslatable(TRUE);
    }

    return $field_storage_definitions;
  }

}
