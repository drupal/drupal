<?php

declare(strict_types=1);

namespace Drupal\unique_field_constraint_test\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for unique_field_constraint_test.
 */
class UniqueFieldConstraintTestHooks {

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() === 'entity_test_string_id') {
      /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
      $fields['name']->addConstraint('UniqueField');
    }
    if ($entity_type->id() === 'entity_test') {
      /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
      $fields['name']->addConstraint('UniqueField');
    }
  }

  /**
   * Implements hook_query_entity_test_access_alter().
   */
  #[Hook('query_entity_test_access_alter')]
  public function queryEntityTestAccessAlter(AlterableInterface $query): void {
    // Set an impossible condition to filter out all entities.
    /** @var \Drupal\Core\Database\Query\Select|\Drupal\Core\Database\Query\AlterableInterface $query */
    $query->condition('entity_test.id', 0);
  }

}
