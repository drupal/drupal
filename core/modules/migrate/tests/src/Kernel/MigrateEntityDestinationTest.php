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
  protected static $modules = [
    'field',
    'migrate_destination_test',
    'node',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
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
    // Test with a migration with a default bundle that has fields.
    $node_with_fields = $this->getMigration('node_with_fields');
    $destination_fields = $node_with_fields->getDestinationPlugin();

    // Test with a migration with a default bundle that does not have fields.
    $node_no_fields = $this->getMigration('node_no_fields');
    $destination_no_fields = $node_no_fields->getDestinationPlugin();

    $this->assertTrue(in_array('nid', array_keys($destination_fields->fields())));
    $this->assertFalse(in_array('field_text', array_keys($destination_fields->fields())));

    $this->assertTrue(in_array('nid', array_keys($destination_no_fields->fields())));
    $this->assertFalse(in_array('field_text', array_keys($destination_no_fields->fields())));

    // Create a text field attached to 'test_node_type_with_fields' node type.
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

    // Test with a user entity.
    $user_with_fields = $this->getMigration('user_with_fields');
    $destination_fields = $user_with_fields->getDestinationPlugin();

    $this->assertTrue(in_array('uid', array_keys($destination_fields->fields())));
    $this->assertFalse(in_array('field_text', array_keys($destination_fields->fields())));

    // Create a text field attached to the user entity.
    FieldStorageConfig::create([
      'type' => 'string',
      'entity_type' => 'user',
      'field_name' => 'field_text',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'user',
      'bundle' => 'user',
      'field_name' => 'field_text',
    ])->save();

    $this->assertTrue(in_array('field_text', array_keys($destination_fields->fields())));
  }

}
