<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\DatabaseStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

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
  public function containerBuild(ContainerBuilder $container) {
    parent::containerBuild($container);

    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.database';
    $container->setParameter('factory.keyvalue', $parameter);
  }

}
