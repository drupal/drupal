<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests exceptions thrown by queries.
 *
 * @group Database
 */
class DatabaseExceptionWrapperTest extends KernelTestBase {

  /**
   * Tests the expected database exception thrown for inexistent tables.
   */
  public function testQueryThrowsDatabaseExceptionWrapperException(): void {
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection()->query('SELECT * FROM {does_not_exist}');
  }

}
