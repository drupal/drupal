<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config\Storage\Checkpoint;

use Drupal\Core\Config\Checkpoint\CheckpointStorageInterface;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests CheckpointStorage operations.
 *
 * @group config
 */
class CheckpointStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'config_test']);
  }

  /**
   * Tests the save and read operations of checkpoint storage.
   */
  public function testConfigSaveAndRead(): void {
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');

    $this->config('system.site')->set('name', 'Test1')->save();
    $check1 = $checkpoint_storage->checkpoint('A');
    $this->config('system.site')->set('name', 'Test2')->save();
    $check2 = $checkpoint_storage->checkpoint('B');
    $this->config('system.site')->set('name', 'Test3')->save();

    $this->assertSame('Test3', $this->config('system.site')->get('name'));
    $this->assertSame('Test1', $checkpoint_storage->read('system.site')['name']);

    // The config listings should be exactly the same.
    $this->assertSame($checkpoint_storage->listAll(), $this->container->get('config.storage')->listAll());

    $checkpoint_storage->setCheckpointToReadFrom($check2);
    $this->assertSame('Test2', $checkpoint_storage->read('system.site')['name']);
    $this->assertSame($checkpoint_storage->listAll(), $this->container->get('config.storage')->listAll());

    $checkpoint_storage->setCheckpointToReadFrom($check1);
    $this->assertSame('Test1', $checkpoint_storage->read('system.site')['name']);
    $this->assertSame($checkpoint_storage->listAll(), $this->container->get('config.storage')->listAll());
  }

  /**
   * Tests the delete operation of checkpoint storage.
   */
  public function testConfigDelete(): void {
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');

    $check1 = $checkpoint_storage->checkpoint('A');
    $this->config('config_test.system')->delete();

    $this->assertFalse($this->container->get('config.storage')->exists('config_test.system'));
    $this->assertTrue($checkpoint_storage->exists('config_test.system'));
    $this->assertSame('bar', $checkpoint_storage->read('config_test.system')['foo']);

    $this->assertContains('config_test.system', $checkpoint_storage->listAll());
    $this->assertContains('config_test.system', $checkpoint_storage->listAll('config_test.'));
    $this->assertNotContains('config_test.system', $checkpoint_storage->listAll('system.'));
    // Should not be part of the active storage anymore.
    $this->assertNotContains('config_test.system', $this->container->get('config.storage')->listAll());

    $check2 = $checkpoint_storage->checkpoint('B');

    $this->config('config_test.system')->set('foo', 'foobar')->save();
    $this->assertTrue($this->container->get('config.storage')->exists('config_test.system'));
    $this->assertTrue($checkpoint_storage->exists('config_test.system'));
    $this->assertSame('bar', $checkpoint_storage->read('config_test.system')['foo']);

    $checkpoint_storage->setCheckpointToReadFrom($check2);
    $this->assertFalse($checkpoint_storage->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->read('config_test.system'));
    $this->assertNotContains('config_test.system', $checkpoint_storage->listAll());

    $checkpoint_storage->setCheckpointToReadFrom($check1);
    $this->assertTrue($checkpoint_storage->exists('config_test.system'));
    $this->assertSame('bar', $checkpoint_storage->read('config_test.system')['foo']);
    $this->assertContains('config_test.system', $checkpoint_storage->listAll());
  }

  /**
   * Tests the create operation of checkpoint storage.
   */
  public function testConfigCreate(): void {
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');

    $this->config('config_test.system')->delete();
    $check1 = $checkpoint_storage->checkpoint('A');
    $this->config('config_test.system')->set('foo', 'foobar')->save();

    $this->assertTrue($this->container->get('config.storage')->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->read('config_test.system'));

    $this->assertNotContains('config_test.system', $checkpoint_storage->listAll());
    $this->assertNotContains('config_test.system', $checkpoint_storage->listAll('config_test.'));
    $this->assertContains('system.site', $checkpoint_storage->listAll('system.'));
    $this->assertContains('config_test.system', $this->container->get('config.storage')->listAll());

    $check2 = $checkpoint_storage->checkpoint('B');
    $this->config('config_test.system')->delete();

    $this->assertFalse($this->container->get('config.storage')->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->read('config_test.system'));

    $this->config('config_test.system')->set('foo', 'foobar')->save();
    $this->assertTrue($this->container->get('config.storage')->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->read('config_test.system'));

    $checkpoint_storage->setCheckpointToReadFrom($check2);
    $this->assertTrue($checkpoint_storage->exists('config_test.system'));
    $this->assertSame('foobar', $checkpoint_storage->read('config_test.system')['foo']);
    $this->assertContains('config_test.system', $checkpoint_storage->listAll());

    $checkpoint_storage->setCheckpointToReadFrom($check1);
    $this->assertFalse($checkpoint_storage->exists('config_test.system'));
    $this->assertFalse($checkpoint_storage->read('config_test.system'));
    $this->assertNotContains('config_test.system', $checkpoint_storage->listAll());
  }

  /**
   * Tests the rename operation of checkpoint storage.
   */
  public function testConfigRename(): void {
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');
    $check1 = $checkpoint_storage->checkpoint('A');
    $this->container->get('config.factory')->rename('config_test.dynamic.dotted.default', 'config_test.dynamic.renamed');
    $this->config('config_test.dynamic.renamed')->set('id', 'renamed')->save();

    $this->assertFalse($checkpoint_storage->exists('config_test.dynamic.renamed'));
    $this->assertTrue($checkpoint_storage->exists('config_test.dynamic.dotted.default'));
    $this->assertSame('dotted.default', $checkpoint_storage->read('config_test.dynamic.dotted.default')['id']);
    $this->assertSame($checkpoint_storage->read('config_test.dynamic.dotted.default')['uuid'], $this->config('config_test.dynamic.renamed')->get('uuid'));

    $check2 = $checkpoint_storage->checkpoint('B');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    // Entity1 will be deleted by the test.
    $entity1 = $storage->create(
      [
        'id' => 'dotted.default',
        'label' => 'Another one',
      ]
    );
    $entity1->save();

    $check3 = $checkpoint_storage->checkpoint('C');

    $checkpoint_storage->setCheckpointToReadFrom($check2);
    $this->assertFalse($checkpoint_storage->exists('config_test.dynamic.dotted.default'));

    $checkpoint_storage->setCheckpointToReadFrom($check3);
    $this->assertTrue($checkpoint_storage->exists('config_test.dynamic.dotted.default'));
    $this->assertNotEquals($checkpoint_storage->read('config_test.dynamic.dotted.default')['uuid'], $this->config('config_test.dynamic.renamed')->get('uuid'));
    $this->assertSame('Another one', $checkpoint_storage->read('config_test.dynamic.dotted.default')['label']);

    $checkpoint_storage->setCheckpointToReadFrom($check1);
    $this->assertSame('Default', $checkpoint_storage->read('config_test.dynamic.dotted.default')['label']);
  }

  /**
   * Tests the revert operation of checkpoint storage.
   */
  public function testRevert(): void {
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');
    $check1 = $checkpoint_storage->checkpoint('A');
    $this->assertTrue($this->container->get('module_installer')->uninstall(['config_test']));
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');
    $check2 = $checkpoint_storage->checkpoint('B');

    $importer = $this->getConfigImporter($checkpoint_storage);
    $config_changelist = $importer->getStorageComparer()->createChangelist()->getChangelist();
    $this->assertContains('config_test.dynamic.dotted.default', $config_changelist['create']);
    $this->assertSame(['core.extension'], $config_changelist['update']);
    $this->assertSame([], $config_changelist['delete']);
    $this->assertSame([], $config_changelist['rename']);

    $importer->import();
    $this->assertSame([], $importer->getErrors());

    $this->assertTrue($this->container->get('module_handler')->moduleExists('config_test'));

    $checkpoint_storage = $this->container->get('config.storage.checkpoint');
    $checkpoint_storage->setCheckpointToReadFrom($check2);

    $importer = $this->getConfigImporter($checkpoint_storage);
    $config_changelist = $importer->getStorageComparer()->createChangelist()->getChangelist();
    $this->assertContains('config_test.dynamic.dotted.default', $config_changelist['delete']);
    $this->assertSame(['core.extension'], $config_changelist['update']);
    $this->assertSame([], $config_changelist['create']);
    $this->assertSame([], $config_changelist['rename']);
    $importer->import();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('config_test'));

    $checkpoint_storage->setCheckpointToReadFrom($check1);
    $importer = $this->getConfigImporter($checkpoint_storage);
    $importer->getStorageComparer()->createChangelist();
    $importer->import();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('config_test'));
  }

  /**
   * Tests the rename operation of checkpoint storage with collections.
   */
  public function testRevertWithCollections(): void {
    $collections = [
      'another_collection',
      'collection.test1',
      'collection.test2',
    ];
    // Set the event listener to return three possible collections.
    // @see \Drupal\config_collection_install_test\EventSubscriber
    \Drupal::state()->set('config_collection_install_test.collection_names', $collections);

    $checkpoint_storage = $this->container->get('config.storage.checkpoint');
    $checkpoint_storage->checkpoint('A');

    // Install the test module.
    $this->assertTrue($this->container->get('module_installer')->install(['config_collection_install_test']));
    $checkpoint_storage = $this->container->get('config.storage.checkpoint');

    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEquals($collections, $active_storage->getAllCollectionNames());
    foreach ($collections as $collection) {
      $collection_storage = $active_storage->createCollection($collection);
      $data = $collection_storage->read('config_collection_install_test.test');
      $this->assertEquals($collection, $data['collection']);
    }

    $check2 = $checkpoint_storage->checkpoint('B');

    $importer = $this->getConfigImporter($checkpoint_storage);
    $storage_comparer = $importer->getStorageComparer();
    $config_changelist = $storage_comparer->createChangelist()->getChangelist();
    $this->assertSame([], $config_changelist['create']);
    $this->assertSame(['core.extension'], $config_changelist['update']);
    $this->assertSame([], $config_changelist['delete']);
    $this->assertSame([], $config_changelist['rename']);
    foreach ($collections as $collection) {
      $config_changelist = $storage_comparer->getChangelist(NULL, $collection);
      $this->assertSame([], $config_changelist['create']);
      $this->assertSame([], $config_changelist['update']);
      $this->assertSame(['config_collection_install_test.test'], $config_changelist['delete'], $collection);
      $this->assertSame([], $config_changelist['rename']);
    }

    $importer->import();
    $this->assertSame([], $importer->getErrors());

    $checkpoint_storage = $this->container->get('config.storage.checkpoint');
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEmpty($active_storage->getAllCollectionNames());
    foreach ($collections as $collection) {
      $collection_storage = $active_storage->createCollection($collection);
      $this->assertFalse($collection_storage->read('config_collection_install_test.test'));
    }

    $checkpoint_storage->setCheckpointToReadFrom($check2);

    $importer = $this->getConfigImporter($checkpoint_storage);

    $storage_comparer = $importer->getStorageComparer();
    $config_changelist = $storage_comparer->createChangelist()->getChangelist();
    $this->assertSame([], $config_changelist['create']);
    $this->assertSame(['core.extension'], $config_changelist['update']);
    $this->assertSame([], $config_changelist['delete']);
    $this->assertSame([], $config_changelist['rename']);
    foreach ($collections as $collection) {
      $config_changelist = $storage_comparer->getChangelist(NULL, $collection);
      $this->assertSame(['config_collection_install_test.test'], $config_changelist['create']);
      $this->assertSame([], $config_changelist['update']);
      $this->assertSame([], $config_changelist['delete'], $collection);
      $this->assertSame([], $config_changelist['rename']);
    }
    $importer->import();
    $this->assertSame([], $importer->getErrors());

    $this->assertTrue($this->container->get('module_handler')->moduleExists('config_collection_install_test'));
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $this->assertEquals($collections, $active_storage->getAllCollectionNames());
    foreach ($collections as $collection) {
      $collection_storage = $active_storage->createCollection($collection);
      $data = $collection_storage->read('config_collection_install_test.test');
      $this->assertEquals($collection, $data['collection']);
    }
  }

  /**
   * Gets the configuration importer.
   */
  private function getConfigImporter(CheckpointStorageInterface $storage): ConfigImporter {
    $storage_comparer = new StorageComparer(
      $storage,
      $this->container->get('config.storage')
    );
    return new ConfigImporter(
      $storage_comparer,
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );
  }

}
