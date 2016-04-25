<?php

namespace Drupal\system\Tests\Entity\Update;

use Drupal\Core\Database\Database;
use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that the entity langcode fields have been updated to varchar_ascii.
 *
 * @group Entity
 */
class LangcodeToAsciiUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that the column collation has been updated on MySQL.
   */
  public function testLangcodeColumnCollation() {
    // Only testable on MySQL.
    // @see https://www.drupal.org/node/301038
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->pass('This test can only run on MySQL');
      return;
    }

    // Check a few different tables.
    $tables = [
      'node_field_data' => ['langcode'],
      'users_field_data' => ['langcode', 'preferred_langcode', 'preferred_admin_langcode'],
    ];
    foreach ($tables as $table => $columns) {
      foreach ($columns as $column) {
        $this->assertEqual('utf8mb4_general_ci', $this->getColumnCollation($table, $column), 'Found correct starting collation for ' . $table . '.' . $column);
      }
    }

    // Apply updates.
    $this->runUpdates();

    foreach ($tables as $table => $columns) {
      foreach ($columns as $column) {
        $this->assertEqual('ascii_general_ci', $this->getColumnCollation($table, $column), 'Found correct updated collation for ' . $table . '.' . $column);
      }
    }
  }

  /**
   * Determine the column collation.
   *
   * @param string $table
   *   The table name.
   * @param string $column
   *   The column name.
   */
  protected function getColumnCollation($table, $column) {
    $query = Database::getConnection()->query("SHOW FULL COLUMNS FROM {" . $table . "}");
    while ($row = $query->fetchAssoc()) {
      if ($row['Field'] === $column) {
        return $row['Collation'];
      }
    }
    $this->fail('No collation found for ' . $table . '.' . $column);
  }

}
