<?php

/**
 * @file
 * Contains Drupal\system\Tests\Database\DatabaseExceptionWrapperTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Database;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests DatabaseExceptionWrapper thrown.
 */
class DatabaseExceptionWrapperTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Database exceptiontests',
      'description' => 'Tests exceptions thrown by queries.',
      'group' => 'Database',
    );
  }

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
