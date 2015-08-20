<?php

/**
 * @file
 * Contains \Drupal\options\Tests\Views\OptionsTestBase.
 */

namespace Drupal\options\Tests\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewKernelTestBase;

/**
 * Base class for options views tests.
 */
abstract class OptionsTestBase extends ViewKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['options', 'options_test_views', 'node', 'user', 'field'];

  /**
   * Stores the nodes used for the different tests.
   *
   * @var array
   */
  protected $nodes = [];

  /**
   * Stores the field values used for the different tests.
   *
   * @var array
   */
  protected $fieldValues = [];

  /**
   * The used field names.
   *
   * @var string[]
   */
  protected $fieldNames;

  protected function setUp() {
    parent::setUp();
    $this->mockStandardInstall();

    ViewTestData::createTestViews(get_class($this), ['options_test_views']);

    $settings = [];
    $settings['type'] = 'article';
    $settings['title'] = $this->randomString();
    $settings['field_test_list_string'][]['value'] = $this->fieldValues[0];
    $settings['field_test_list_integer'][]['value'] = 0;

    $node = Node::create($settings);
    $node->save();

    $this->nodes[] = $node;
    $node = $node->createDuplicate();
    $node->save();
    $this->nodes[] = $node;
  }

  /**
   * Provides a workaround for the inability to use the standard profile.
   *
   * @see https://www.drupal.org/node/1708692
   */
  protected function mockStandardInstall() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create(
      ['type' => 'article']
    )->save();
    $this->fieldValues = [
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    $this->fieldNames = ['field_test_list_string', 'field_test_list_integer'];

    // Create two field entities.
    FieldStorageConfig::create([
      'field_name' => $this->fieldNames[0],
      'entity_type' => 'node',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          $this->fieldValues[0] => $this->fieldValues[0],
          $this->fieldValues[1] => $this->fieldValues[1],
        ],
      ],
    ])->save();
    FieldStorageConfig::create([
      'field_name' => $this->fieldNames[1],
      'entity_type' => 'node',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          $this->fieldValues[0],
          $this->fieldValues[1],
        ],
      ],
    ])->save();
    foreach ($this->fieldNames as $field_name) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'label' => 'Test options list field',
        'bundle' => 'article',
      ])->save();
    }
  }

}
