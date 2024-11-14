<?php

declare(strict_types=1);

namespace Drupal\keyvalue_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for keyvalue_test.
 */
class KeyvalueTestHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (isset($entity_types['entity_test_label'])) {
      $entity_types['entity_test_label']->setStorageClass('Drupal\Core\Entity\KeyValueStore\KeyValueContentEntityStorage');
      $entity_keys = $entity_types['entity_test_label']->getKeys();
      $entity_types['entity_test_label']->set('entity_keys', $entity_keys + ['uuid' => 'uuid']);
      $entity_types['entity_test_label']->set('provider', 'keyvalue_test');
    }
  }

}
