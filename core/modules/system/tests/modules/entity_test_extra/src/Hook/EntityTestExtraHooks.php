<?php

declare(strict_types=1);

namespace Drupal\entity_test_extra\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test_extra.
 */
class EntityTestExtraHooks {

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    return \Drupal::state()->get($entity_type->id() . '.additional_base_field_definitions', []);
  }

  /**
   * Implements hook_entity_field_storage_info().
   */
  #[Hook('entity_field_storage_info')]
  public function entityFieldStorageInfo(EntityTypeInterface $entity_type) {
    return \Drupal::state()->get($entity_type->id() . '.additional_field_storage_definitions', []);
  }

  /**
   * Implements hook_entity_bundle_field_info().
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    return \Drupal::state()->get($entity_type->id() . '.' . $bundle . '.additional_bundle_field_definitions', []);
  }

}
