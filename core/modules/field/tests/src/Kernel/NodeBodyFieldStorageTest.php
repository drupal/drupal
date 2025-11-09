<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node body field storage.
 */
#[Group('field')]
#[RunTestsInSeparateProcesses]
class NodeBodyFieldStorageTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Necessary for module uninstall.
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);
  }

  /**
   * Tests node body field storage persistence even if there are no instances.
   */
  public function testFieldOverrides(): void {
    FieldStorageConfig::create([
      'field_name' => 'body',
      'type' => 'text_long',
      'entity_type' => 'node',
      'cardinality' => 1,
      'persist_with_no_fields' => TRUE,
    ])->save();
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertNotEmpty($field_storage, 'Node body field storage exists.');
    $this->createContentType(['name' => 'Ponies', 'type' => 'ponies']);

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
