<?php

/**
 * @file
 * Contains Drupal\system\Tests\Database\DatabaseExceptionWrapperTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Database;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests exceptions thrown by queries.
 *
 * @group Database
 */
class DatabaseExceptionWrapperTest extends KernelTestBase {

  function testDatabaseExceptionWrapper() {
    $connection = Database::getConnection();
    $query = $connection->prepare('bananas');
    try {
      $connection->query($query);
      $this->fail('The expected exception is not thrown.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->pass('The expected exception has been thrown.');
    }
  }

}
