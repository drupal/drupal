<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeTypeInterface;

/**
 * Trait for node permission testing.
 *
 * This trait is meant to be used only by test classes.
 */
trait NodeAccessTrait {

  /**
   * Adds the private field to a node type.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   A node type entity.
   *
   * @see \Drupal\node_access_test\Hook\NodeAccessTestHooks::nodeGrants()
   * @see \Drupal\Tests\node\Functional\NodeQueryAlterTest
   * @see \Drupal\Tests\node\Functional\NodeAccessBaseTableTest
   */
  public function addPrivateField(NodeTypeInterface $type): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'private',
      'entity_type' => 'node',
      'type' => 'integer',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'private',
      'entity_type' => 'node',
      'bundle' => $type->id(),
      'label' => 'Private',
    ]);
    $field->save();

    // Assign widget settings for the 'default' form mode.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $type->id())
      ->setComponent('private', [
        'type' => 'number',
      ])
      ->save();
  }

}
