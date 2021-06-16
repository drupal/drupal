<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests node body field storage.
 *
 * @group node
 */
class NodeBodyFieldStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'node',
    'text',
    'filter',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    // Necessary for module uninstall.
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);
  }

  /**
   * Tests node body field storage persistence even if there are no instances.
   */
  public function testFieldOverrides() {
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertNotEmpty($field_storage, 'Node body field storage exists.');
    $type = NodeType::create(['name' => 'Ponies', 'type' => 'ponies']);
    $type->save();
    node_add_body_field($type);
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertCount(1, $field_storage->getBundles(), 'Node body field storage is being used on the new node type.');
    $field = FieldConfig::loadByName('node', 'ponies', 'body');
    $field->delete();
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertCount(0, $field_storage->getBundles(), 'Node body field storage exists after deleting the only instance of a field.');
    \Drupal::service('module_installer')->uninstall(['node']);
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertNull($field_storage, 'Node body field storage does not exist after uninstalling the Node module.');
  }

}
