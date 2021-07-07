<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Tests the destination Entity plugin.
 *
 * @group migrate
 */
class MigrateEntityDestinationTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'field',
    'migrate_destination_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create([
      'type' => 'test_node_type_no_field',
      'name' => 'Test node type without fields',
    ])->save();

    NodeType::create([
      'type' => 'test_node_type_with_fields',
      'name' => 'Test node type with fields',
    ])->save();
  }

  /**
   * Test destination fields() method.
   */
  public function testDestinationField() {
    $node_with_fields = $this->getMigration('node_with_fields');
    $destination_fields = $node_with_fields->getDestinationPlugin();

    $node_no_fields = $this->getMigration('node_no_fields');
    $destination_no_fields = $node_no_fields->getDestinationPlugin();

    $this->assertTrue(in_array('nid', array_keys($destination_fields->fields())));
    $this->assertFalse(in_array('field_text', array_keys($destination_fields->fields())));

    $this->assertTrue(in_array('nid', array_keys($destination_no_fields->fields())));
    $this->assertFalse(in_array('field_text', array_keys($destination_no_fields->fields())));

    // Create a text field attached to 'test_node_type_2' node-type.
    FieldStorageConfig::create([
      'type' => 'string',
      'entity_type' => 'node',
      'field_name' => 'field_text',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'test_node_type_with_fields',
      'field_name' => 'field_text',
    ])->save();

    $this->assertTrue(in_array('field_text', array_keys($destination_fields->fields())));
    // The destination_bundle_entity migration has default bundle of
    // test_node_type so it shouldn't show the fields on other node types.
    $this->assertFalse(in_array('field_text', array_keys($destination_no_fields->fields())));

  }

}
