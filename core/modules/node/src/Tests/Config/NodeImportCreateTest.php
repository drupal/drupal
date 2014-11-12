<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Config\NodeImportCreateTest.
 */

namespace Drupal\node\Tests\Config;

use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Create content types during config create method invocation.
 *
 * @group node
 */
class NodeImportCreateTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field', 'text', 'system', 'user', 'entity_reference');

  /**
   * Set the default field storage backend for fields created during tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');

    // Set default storage backend.
    $this->installConfig(array('field'));
  }

  /**
   * Tests creating a content type during default config import.
   */
  public function testImportCreateDefault() {
    $node_type_id = 'default';

    // Check that the content type does not exist yet.
    $this->assertFalse(entity_load('node_type', $node_type_id));

    // Enable node_test_config module and check that the content type
    // shipped in the module's default config is created.
    $this->container->get('module_handler')->install(array('node_test_config'));
    $node_type = entity_load('node_type', $node_type_id);
    $this->assertTrue($node_type, 'The default content type was created.');
  }

  /**
   * Tests creating a content type during config import.
   */
  public function testImportCreate() {
    $node_type_id = 'import';
    $node_type_config_name = "node.type.$node_type_id";

    // Simulate config data to import.
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);
    // Manually add new node type.
    $src_dir = drupal_get_path('module', 'node_test_config') . '/staging';
    $target_dir = $this->configDirectories[CONFIG_STAGING_DIRECTORY];
    $this->assertTrue(file_unmanaged_copy("$src_dir/$node_type_config_name.yml", "$target_dir/$node_type_config_name.yml"));

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the content type was created.
    $node_type = entity_load('node_type', $node_type_id);
    $this->assertTrue($node_type, 'Import node type from staging was created.');
    $this->assertFalse(FieldConfig::loadByName('node', $node_type_id, 'body'));
  }

}
