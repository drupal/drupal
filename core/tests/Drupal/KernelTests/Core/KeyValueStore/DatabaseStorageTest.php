<?php

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;

/**
 * Tests the key-value database storage.
 *
 * @group KeyValueStore
 */
class DatabaseStorageTest extends StorageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['key_value']);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.database';
    $container->setParameter('factory.keyvalue', $parameter);
  }

}
