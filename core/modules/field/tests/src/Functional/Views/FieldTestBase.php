<?php

namespace Drupal\Tests\field\Functional\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides some helper methods for testing fieldapi integration into views.
 *
 * @todo Test on a generic entity not on a node. What has to be tested:
 *   - Make sure that every wanted field is added to the according entity type.
 *   - Make sure the joins are done correctly.
 *   - Use basic fields and make sure that the full wanted object is built.
 *   - Use relationships between different entity types, for example node and
 *     the node author(user).
 */
abstract class FieldTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'field_test_views'];

  /**
   * Stores the field definitions used by the test.
   *
   * @var array
   */
  public $fieldStorages;

  /**
   * Stores the fields of the field storage. They have the same keys as the
   * field storages.
   *
   * @var array
   */
  public $fields;

  protected function setUp($import_test_views = TRUE, $modules = ['field_test_views']) {
    parent::setUp($import_test_views, $modules);

    // Ensure the page node type exists.
    NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ])->save();
  }

  public function setUpFieldStorages($amount = 3, $type = 'string') {
    // Create three fields.
    $field_names = [];
    for ($i = 0; $i < $amount; $i++) {
      $field_names[$i] = 'field_name_' . $i;
      $this->fieldStorages[$i] = FieldStorageConfig::create([
        'field_name' => $field_names[$i],
        'entity_type' => 'node',
        'type' => $type,
      ]);
      $this->fieldStorages[$i]->save();
    }
    return $field_names;
  }

  public function setUpFields($bundle = 'page') {
    foreach ($this->fieldStorages as $key => $field_storage) {
      $this->fields[$key] = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
      ]);
      $this->fields[$key]->save();
    }
  }

}
