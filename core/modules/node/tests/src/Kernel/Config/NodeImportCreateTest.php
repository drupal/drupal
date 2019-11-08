<?php

namespace Drupal\Tests\node\Kernel\Config;

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Create content types during config create method invocation.
 *
 * @group node
 */
class NodeImportCreateTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'field', 'text', 'system', 'user'];

  /**
   * Set the default field storage backend for fields created during tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');

    // Set default storage backend.
    $this->installConfig(['system', 'field']);
  }

  /**
   * Tests creating a content type during default config import.
   */
  public function testImportCreateDefault() {
    $node_type_id = 'default';

    // Check that the content type does not exist yet.
    $this->assertNull(NodeType::load($node_type_id));

    // Enable node_test_config module and check that the content type
    // shipped in the module's default config is created.
    $this->container->get('module_installer')->install(['node_test_config']);
    $node_type = NodeType::load($node_type_id);
    $this->assertNotEmpty($node_type, 'The default content type was created.');
  }

  /**
   * Tests creating a content type during config import.
   */
  public function testImportCreate() {
    $node_type_id = 'import';
    $node_type_config_name = "node.type.$node_type_id";

    // Simulate config data to import.
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);
    // Manually add new node type.
    $src_dir = __DIR__ . '/../../../modules/node_test_config/sync';
    $target_dir = Settings::get('config_sync_directory');
    $this->assertNotFalse(\Drupal::service('file_system')->copy("$src_dir/$node_type_config_name.yml", "$target_dir/$node_type_config_name.yml"));

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the content type was created.
    $node_type = NodeType::load($node_type_id);
    $this->assertNotEmpty($node_type, 'Import node type from sync was created.');
    $this->assertNull(FieldConfig::loadByName('node', $node_type_id, 'body'));
  }

}
