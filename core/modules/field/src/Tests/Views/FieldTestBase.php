<?php

namespace Drupal\field\Tests\Views;

@trigger_error(__NAMESPACE__ . '\FieldTestBase is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\field\Functional\Views\FieldTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
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
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 * \Drupal\Tests\field\Functional\Views\FieldTestBase instead.
 *
 * @see https://www.drupal.org/node/2989020
 */
abstract class FieldTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'field_test_views'];

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

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Ensure the page node type exists.
    NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ])->save();

    ViewTestData::createTestViews(get_class($this), ['field_test_views']);
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
