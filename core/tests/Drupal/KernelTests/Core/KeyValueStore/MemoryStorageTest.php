<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the key-value memory storage.
 */
#[Group('KeyValueStore')]
#[RunTestsInSeparateProcesses]
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
