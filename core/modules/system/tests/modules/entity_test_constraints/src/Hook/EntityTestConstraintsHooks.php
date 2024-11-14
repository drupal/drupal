<?php

declare(strict_types=1);

namespace Drupal\entity_test_constraints\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test_constraints.
 */
class EntityTestConstraintsHooks {

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types) {
    if ($extra = \Drupal::state()->get('entity_test_constraints.build')) {
      foreach ($extra as $id => $option) {
        $entity_types['entity_test_constraints']->addConstraint($id, $option);
      }
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    if ($alter = \Drupal::state()->get('entity_test_constraints.alter')) {
      $entity_types['entity_test_constraints']->setConstraints($alter);
    }
  }

}
