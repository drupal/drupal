<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;

/**
 * Tests the key-value memory storage.
 *
 * @group KeyValueStore
 */
class MemoryStorageTest extends StorageTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->register('keyvalue.memory', 'Drupal\Core\KeyValueStore\KeyValueMemoryFactory');
    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.memory';
    $container->setParameter('factory.keyvalue', $parameter);
  }

}
