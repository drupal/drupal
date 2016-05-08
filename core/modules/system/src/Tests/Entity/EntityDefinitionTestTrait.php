<?php

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\FieldStorageDefinition;

/**
 * Provides some test methods used to update existing entity definitions.
 */
trait EntityDefinitionTestTrait {

  /**
   * Enables a new entity type definition.
   */
  protected function enableNewEntityType() {
    $this->state->set('entity_test_new', TRUE);
    $this->entityManager->clearCachedDefinitions();
    $this->entityDefinitionUpdateManager->applyUpdates();
  }

  /**
   * Resets the entity type definition.
   */
  protected function resetEntityType() {
    $this->state->set('entity_test_update.entity_type', NULL);
    $this->entityManager->clearCachedDefinitions();
    $this->entityDefinitionUpdateManager->applyUpdates();
  }

  /**
   * Updates the 'entity_test_update' entity type to revisionable.
   */
  protected function updateEntityTypeToRevisionable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $keys = $entity_type->getKeys();
    $keys['revision'] = 'revision_id';
    $entity_type->set('entity_keys', $keys);

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Updates the 'entity_test_update' entity type not revisionable.
   */
  protected function updateEntityTypeToNotRevisionable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $keys = $entity_type->getKeys();
    unset($keys['revision']);
    $entity_type->set('entity_keys', $keys);

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Updates the 'entity_test_update' entity type to translatable.
   */
  protected function updateEntityTypeToTranslatable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $entity_type->set('translatable', TRUE);
    $entity_type->set('data_table', 'entity_test_update_data');

    if ($entity_type->isRevisionable()) {
      $entity_type->set('revision_data_table', 'entity_test_update_revision_data');
    }

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Updates the 'entity_test_update' entity type to not translatable.
   */
  protected function updateEntityTypeToNotTranslatable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $entity_type->set('translatable', FALSE);
    $entity_type->set('data_table', NULL);

    if ($entity_type->isRevisionable()) {
      $entity_type->set('revision_data_table', NULL);
    }

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Adds a new base field to the 'entity_test_update' entity type.
   *
   * @param string $type
   *   (optional) The field type for the new field. Defaults to 'string'.
   */
  protected function addBaseField($type = 'string') {
    $definitions['new_base_field'] = BaseFieldDefinition::create($type)
      ->setName('new_base_field')
      ->setLabel(t('A new base field'));
    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);
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
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');
    $entity_keys = $entity_type->getKeys();
    $entity_keys['new_base_field'] = 'new_base_field';
    $entity_type->set('entity_keys', $entity_keys);
    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Removes the new base field from the 'entity_test_update' entity type.
   */
  protected function removeBaseField() {
    $this->state->delete('entity_test_update.additional_base_field_definitions');
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
   */
  protected function addBundleField($type = 'string') {
    $definitions['new_bundle_field'] = FieldStorageDefinition::create($type)
      ->setName('new_bundle_field')
      ->setLabel(t('A new bundle field'))
      ->setTargetEntityTypeId('entity_test_update');
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
    $indexes = array(
      'entity_test_update__new_index' => array('name', 'user_id'),
    );
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
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $entity_type->set('base_table', 'entity_test_update_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Renames the data table to 'entity_test_update_data_new'.
   */
  protected function renameDataTable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $entity_type->set('data_table', 'entity_test_update_data_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Renames the revision table to 'entity_test_update_revision_new'.
   */
  protected function renameRevisionBaseTable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $entity_type->set('revision_table', 'entity_test_update_revision_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Renames the revision data table to 'entity_test_update_revision_data_new'.
   */
  protected function renameRevisionDataTable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');

    $entity_type->set('revision_data_table', 'entity_test_update_revision_data_new');

    $this->state->set('entity_test_update.entity_type', $entity_type);
  }

  /**
   * Removes the entity type.
   */
  protected function deleteEntityType() {
    $this->state->set('entity_test_update.entity_type', 'null');
  }

}
