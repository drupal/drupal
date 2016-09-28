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
  public static $modules = array('system');

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('key_value'));
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
