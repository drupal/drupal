<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFieldDefaultValueTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests entity reference field default values storage in CMI.
 *
 * @group entity_reference
 */
class EntityReferenceFieldDefaultValueTest extends WebTestBase {
  use SchemaCheckTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'field_ui', 'node');

  /**
   * A user with permission to administer content types, node fields, etc.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create default content type.
    $this->drupalCreateContentType(array('type' => 'reference_content'));
    $this->drupalCreateContentType(array('type' => 'referenced_content'));

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node fields', 'administer node form display', 'bypass node access'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that default values are correctly translated to UUIDs in config.
   */
  function testEntityReferenceDefaultValue() {
    // Create a node to be referenced.
    $referenced_node = $this->drupalCreateNode(array('type' => 'referenced_content'));

    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => array('target_type' => 'node'),
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'reference_content',
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array('referenced_content'),
          'sort' => array('field' => '_none'),
        ),
      ),
    ));
    $field->save();

    // Set created node as default_value.
    $field_edit = array(
      'default_value_input[' . $field_name . '][0][target_id]' => $referenced_node->getTitle() . ' (' .$referenced_node->id() . ')',
    );
    $this->drupalPostForm('admin/structure/types/manage/reference_content/fields/node.reference_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/reference_content/fields/node.reference_content.' . $field_name);
    $this->assertRaw('name="default_value_input[' . $field_name . '][0][target_id]" value="' . $referenced_node->getTitle() .' (' .$referenced_node->id() . ')', 'The default value is selected in instance settings page');

    // Check if the ID has been converted to UUID in config entity.
    $config_entity = $this->config('field.field.node.reference_content.' . $field_name)->get();
    $this->assertTrue(isset($config_entity['default_value'][0]['target_uuid']), 'Default value contains target_uuid property');
    $this->assertEqual($config_entity['default_value'][0]['target_uuid'], $referenced_node->uuid(), 'Content uuid and config entity uuid are the same');
    // Ensure the configuration has the expected dependency on the entity that
    // is being used a default value.
    $this->assertEqual(array($referenced_node->getConfigDependencyName()), $config_entity['dependencies']['content']);

    // Clear field definitions cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create a new node to check that UUID has been converted to numeric ID.
    $new_node = entity_create('node', array('type' => 'reference_content'));
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->target_id, $referenced_node->id());

    // Ensure that the entity reference config schemas are correct.
    $field_config = $this->config('field.field.node.reference_content.' . $field_name);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $field_config->getName(), $field_config->get());
    $field_storage_config = $this->config('field.storage.node.' . $field_name);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $field_storage_config->getName(), $field_storage_config->get());
  }

  /**
   * Tests that dependencies due to default values can be removed.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::onDependencyRemoval()
   */
  function testEntityReferenceDefaultConfigValue() {
    // Create a node to be referenced.
    $referenced_node_type = $this->drupalCreateContentType(array('type' => 'referenced_config_to_delete'));
    $referenced_node_type2 = $this->drupalCreateContentType(array('type' => 'referenced_config_to_preserve'));

    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => array('target_type' => 'node_type'),
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'reference_content',
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          'sort' => array('field' => '_none'),
        ),
      ),
    ));
    $field->save();

    // Set created node as default_value.
    $field_edit = array(
      'default_value_input[' . $field_name . '][0][target_id]' => $referenced_node_type->label() . ' (' .$referenced_node_type->id() . ')',
      'default_value_input[' . $field_name . '][1][target_id]' => $referenced_node_type2->label() . ' (' .$referenced_node_type2->id() . ')',
    );
    $this->drupalPostForm('admin/structure/types/manage/reference_content/fields/node.reference_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that the field has a dependency on the default value.
    $config_entity = $this->config('field.field.node.reference_content.' . $field_name)->get();
    $this->assertTrue(in_array($referenced_node_type->getConfigDependencyName(), $config_entity['dependencies']['config'], TRUE), 'The node type referenced_config_to_delete is a dependency of the field.');
    $this->assertTrue(in_array($referenced_node_type2->getConfigDependencyName(), $config_entity['dependencies']['config'], TRUE), 'The node type referenced_config_to_preserve is a dependency of the field.');

    // Check that the field does not have a dependency on the default value
    // after deleting the node type.
    $referenced_node_type->delete();
    $config_entity = $this->config('field.field.node.reference_content.' . $field_name)->get();
    $this->assertFalse(in_array($referenced_node_type->getConfigDependencyName(), $config_entity['dependencies']['config'], TRUE), 'The node type referenced_config_to_delete not a dependency of the field.');
    $this->assertTrue(in_array($referenced_node_type2->getConfigDependencyName(), $config_entity['dependencies']['config'], TRUE), 'The node type referenced_config_to_preserve is a dependency of the field.');
  }

}
