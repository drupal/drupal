<?php

declare(strict_types=1);

namespace Drupal\entity_test_update\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test_update.
 */
class EntityTestUpdateHooks {

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    // Add a base field that will be used to test that fields added through
    // hook_entity_base_field_info() are handled correctly during a schema
    // conversion (e.g. from non-revisionable to revisionable).
    $fields = [];
    if ($entity_type->id() == 'entity_test_update') {
      $fields['test_entity_base_field_info'] = BaseFieldDefinition::create('string')->setLabel(new TranslatableMarkup('Field added by hook_entity_base_field_info()'))->setTranslatable(TRUE)->setRevisionable(TRUE);
    }
    return $fields;
  }

  /**
   * Implements hook_entity_field_storage_info().
   */
  #[Hook('entity_field_storage_info')]
  public function entityFieldStorageInfo(EntityTypeInterface $entity_type): array {
    if ($entity_type->id() == 'entity_test_update') {
      return \Drupal::state()->get('entity_test_update.additional_field_storage_definitions', []);
    }
    return [];
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // Allow entity_test_update tests to override the entity type definition.
    $entity_type = \Drupal::state()->get('entity_test_update.entity_type', $entity_types['entity_test_update']);
    if ($entity_type !== 'null') {
      $entity_types['entity_test_update'] = $entity_type;
    }
    else {
      unset($entity_types['entity_test_update']);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for the 'view' entity type.
   */
  #[Hook('view_presave')]
  public function viewPresave(EntityInterface $entity): void {
    if (\Drupal::state()->get('entity_test_update.throw_view_exception') === $entity->id()) {
      throw new \LogicException('The view could not be saved.');
    }
  }

}
