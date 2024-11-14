<?php

declare(strict_types=1);

namespace Drupal\field_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_test.
 */
class FieldTestEntityHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    foreach (field_test_entity_info_translatable() as $entity_type => $translatable) {
      $entity_types[$entity_type]->set('translatable', $translatable);
    }
  }

}
