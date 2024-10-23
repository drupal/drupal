<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;
use Drupal\Core\KeyValueStore\KeyValueFactory;

/**
 * Tests the key-value database storage.
 *
 * @group KeyValueStore
 */
class DatabaseStorageTest extends StorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->factory = 'keyvalue.database';
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.database';
    $container->setParameter('factory.keyvalue', $parameter);
  }

  /**
   * Tests asynchronous table creation.
   */
  public function testConcurrent(): void {
    $this->markTestSkipped("Skipped due to frequent random test failures. See https://www.drupal.org/project/drupal/issues/3398063");
    if (!function_exists('pcntl_fork')) {
      $this->markTestSkipped('Requires the pcntl_fork() function');
    }

    $functions = [];
    for ($i = 1; $i <= 10; $i++) {
      $functions[] = 'set';
      $functions[] = 'getAll';
    }

    $default_connection = Database::getConnectionInfo();
    Database::removeConnection('default');

    $time_to_start = microtime(TRUE) + 0.1;

    // This loop creates a new fork to set or get key values keys.
    foreach ($functions as $i => $function) {
      $pid = pcntl_fork();
      if ($pid == -1) {
        $this->fail("Error forking");
      }
      elseif ($pid == 0) {
        Database::addConnectionInfo('default' . $i, 'default', $default_connection['default']);
        Database::setActiveConnection('default' . $i);
        // Create a new factory using the new connection to avoid problems with
        // forks closing the database connections.
        $factory = new KeyValueDatabaseFactory($this->container->get('serialization.phpserialize'), Database::getConnection());
        $store = $factory->get('test');
        // Sleep so that all the forks start at the same time.
        usleep((int) (($time_to_start - microtime(TRUE)) * 1000000));
        if ($function === 'getAll') {
          $this->assertIsArray($store->getAll());
        }
        else {
          $this->assertNull($store->set('foo' . $i, 'bar'));
        }
        exit();
      }
    }

    // This while loop holds the parent process until all the child threads
    // are complete - at which point the script continues to execute.
    while (pcntl_waitpid(0, $status) != -1);

    Database::addConnectionInfo('default', 'default', $default_connection['default']);
    $factory = new KeyValueDatabaseFactory($this->container->get('serialization.phpserialize'), Database::getConnection());
    $store = $factory->get('test');
    $this->assertCount(10, $store->getAll());
  }

}
