<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\TemporaryQueryTestBase;

/**
 * Tests the temporary query functionality.
 *
 * @group Database
 */
class TemporaryQueryTest extends TemporaryQueryTestBase {

  /**
   * Confirms that temporary tables work.
   */
  public function testTemporaryQuery(): void {
    parent::testTemporaryQuery();

    $connection = $this->getConnection();

    $table_name_test = $connection->queryTemporary('SELECT [name] FROM {test}', []);

    // Assert that the table is indeed a temporary one.
    $temporary_table_info = $connection->query("SELECT * FROM pg_class WHERE relname LIKE '%$table_name_test%'")->fetch();
    $this->assertEquals("t", $temporary_table_info->relpersistence);

    // Assert that both have the same field names.
    $normal_table_fields = $connection->query("SELECT * FROM {test}")->fetch();
    $temp_table_name = $connection->queryTemporary('SELECT * FROM {test}');
    $temp_table_fields = $connection->query("SELECT * FROM {" . $temp_table_name . "}")->fetch();

    $normal_table_fields = array_keys(get_object_vars($normal_table_fields));
    $temp_table_fields = array_keys(get_object_vars($temp_table_fields));

    $this->assertEmpty(array_diff($normal_table_fields, $temp_table_fields));
  }

}
