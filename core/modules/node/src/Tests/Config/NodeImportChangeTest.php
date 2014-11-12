<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Config\NodeImportChangeTest.
 */

namespace Drupal\node\Tests\Config;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Change content types during config create method invocation.
 *
 * @group node
 */
class NodeImportChangeTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field', 'text', 'system', 'node_test_config', 'user', 'entity_reference');

  /**
   * Set the default field storage backend for fields created during tests.
   */
  protected function setUp() {
    parent::setUp();

    // Set default storage backend.
    $this->installConfig(array('field', 'node_test_config'));
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
