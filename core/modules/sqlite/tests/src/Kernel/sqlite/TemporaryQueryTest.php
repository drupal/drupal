<?php

namespace Drupal\Tests\sqlite\Kernel\sqlite;

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
  public function testTemporaryQuery() {
    parent::testTemporaryQuery();

    $connection = $this->getConnection();

    $table_name_test = $connection->queryTemporary('SELECT [name] FROM {test}', []);

    // Assert that the table is indeed a temporary one.
    $this->stringContains("temp.", $table_name_test);

    // Assert that both have the same field names.
    $normal_table_fields = $connection->query("SELECT * FROM {test}")->fetch();
    $temp_table_name = $connection->queryTemporary('SELECT * FROM {test}');
    $temp_table_fields = $connection->query("SELECT * FROM $temp_table_name")->fetch();

    $normal_table_fields = array_keys(get_object_vars($normal_table_fields));
    $temp_table_fields = array_keys(get_object_vars($temp_table_fields));

    $this->assertEmpty(array_diff($normal_table_fields, $temp_table_fields));
  }

}
