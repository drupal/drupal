<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Config\NodeImportChangeTest.
 */

namespace Drupal\node\Tests\Config;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests updating content types as part of config import.
 */
class NodeImportChangeTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity', 'field', 'text', 'system', 'node_test_config', 'user');

  /**
   * Set the default field storage backend for fields created during tests.
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', array('config_snapshot'));

    // Set default storage backend.
    $this->installConfig(array('field', 'node_test_config'));
  }

  public static function getInfo() {
    return array(
      'name' => 'Node config change tests',
      'description' => 'Change content types during config create method invocation.',
      'group' => 'Node',
    );
  }

  /**
   * Tests importing an updated content type.
   */
  public function testImportChange() {
    $node_type_id = 'default';
    $node_type_config_name = "node.type.$node_type_id";

    // Simulate config data to import:
    // - a modified version (modified label) of the node type config.
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);

    $node_type = $active->read($node_type_config_name);
    $new_label = 'Test update import field';
    $node_type['name'] = $new_label;
    // Save as files in the the staging directory.
    $staging->write($node_type_config_name, $node_type);

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the updated config was correctly imported.
    $node_type = entity_load('node_type', $node_type_id);
    $this->assertEqual($node_type->label(), $new_label, 'Node type name has been updated.');
  }

}
