<?php

namespace Drupal\Tests\node\Kernel\Config;

use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Change content types during config create method invocation.
 *
 * @group node
 */
class NodeImportChangeTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'system',
    'node_test_config',
    'user',
  ];

  /**
   * Set the default field storage backend for fields created during tests.
   */
  protected function setUp(): void {
    parent::setUp();

    // Set default storage backend.
    $this->installConfig(['system', 'field', 'node_test_config']);
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
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    $node_type = $active->read($node_type_config_name);
    $new_label = 'Test update import field';
    $node_type['name'] = $new_label;
    // Save as files in the sync directory.
    $sync->write($node_type_config_name, $node_type);

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the updated config was correctly imported.
    $node_type = NodeType::load($node_type_id);
    $this->assertEquals($new_label, $node_type->label(), 'Node type name has been updated.');
  }

}
