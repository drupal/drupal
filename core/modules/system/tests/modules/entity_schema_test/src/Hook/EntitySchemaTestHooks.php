<?php

declare(strict_types=1);

namespace Drupal\entity_schema_test\Hook;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_schema_test.
 */
class EntitySchemaTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // Allow a test to tell us whether or not to alter the entity type.
    if (\Drupal::state()->get('entity_schema_update')) {
      $entity_type = $entity_types['entity_test_update'];
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $entity_type->set('translatable', TRUE);
        $entity_type->set('data_table', 'entity_test_update_data');
        // Update the keys with a revision ID.
        $keys = $entity_type->getKeys();
        $keys['revision'] = 'revision_id';
        $entity_type->set('entity_keys', $keys);
        $entity_type->setRevisionMetadataKey('revision_log_message', 'revision_log');
      }
    }
  }

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    if ($entity_type->id() == 'entity_test_update') {
      $definitions['custom_base_field'] = BaseFieldDefinition::create('string')->setName('custom_base_field')->setLabel($this->t('A custom base field'));
      if (\Drupal::state()->get('entity_schema_update')) {
        $definitions += EntityTestMulRev::baseFieldDefinitions($entity_type);
        // And add a revision log.
        $definitions['revision_log'] = BaseFieldDefinition::create('string_long')->setLabel($this->t('Revision log message'))->setDescription($this->t('The log entry explaining the changes in this revision.'))->setRevisionable(TRUE);
      }
      return $definitions;
    }
    return [];
  }

  /**
   * Implements hook_entity_field_storage_info().
   */
  #[Hook('entity_field_storage_info')]
  public function entityFieldStorageInfo(EntityTypeInterface $entity_type): array {
    if ($entity_type->id() == 'entity_test_update') {
      $definitions['custom_bundle_field'] = FieldStorageDefinition::create('string')->setName('custom_bundle_field')->setLabel($this->t('A custom bundle field'))->setRevisionable(TRUE)->setTargetEntityTypeId($entity_type->id());
      return $definitions;
    }
    return [];
  }

  /**
   * Implements hook_entity_bundle_field_info().
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle): array {
    if ($entity_type->id() == 'entity_test_update' && $bundle == 'custom') {
      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $custom_bundle_field_storage */
      $custom_bundle_field_storage = $this->entityFieldStorageInfo($entity_type)['custom_bundle_field'];
      $definitions[$custom_bundle_field_storage->getName()] = FieldDefinition::createFromFieldStorageDefinition($custom_bundle_field_storage);
      return $definitions;
    }
    return [];
  }

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate($entity_type_id, $bundle): void {
    if ($entity_type_id == 'entity_test_update' && $bundle == 'custom') {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
      // Notify the entity storage that we just created a new field.
      \Drupal::service('field_definition.listener')->onFieldDefinitionCreate($field_definitions['custom_bundle_field']);
    }
  }

  /**
   * Implements hook_entity_bundle_delete().
   */
  #[Hook('entity_bundle_delete')]
  public function entityBundleDelete($entity_type_id, $bundle): void {
    if ($entity_type_id == 'entity_test_update' && $bundle == 'custom') {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
      // Notify the entity storage that our field is gone.
      \Drupal::service('field_definition.listener')->onFieldDefinitionDelete($field_definitions['custom_bundle_field']);
      \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionDelete($field_definitions['custom_bundle_field']->getFieldStorageDefinition());
    }
  }

}
