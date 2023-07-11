<?php

namespace Drupal\Tests\pgsql\Functional\Database;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Core\Database\Database;
use Drupal\pgsql\Update10101;

// cSpell:ignore objid refobjid regclass attname attrelid attnum refobjsubid

/**
 * Tests that any unowned sequences created previously have a table owner.
 *
 * The update path only applies to Drupal sites using the pgsql driver.
 *
 * @group Database
 */
class PostgreSqlSequenceUpdateTest extends UpdatePathTestBase {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function runDbTasks() {
    parent::runDbTasks();
    $this->connection = Database::getConnection();
    if ($this->connection->driver() !== 'pgsql') {
      $this->markTestSkipped('This test only works with the pgsql driver');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-9.pgsql-orphan-sequence.php',
    ];
  }

  /**
   * Asserts that a newly created sequence has the correct ownership.
   */
  public function testPostgreSqlSequenceUpdate() {
    $this->assertFalse($this->getSequenceOwner('pgsql_sequence_test', 'sequence_field'));

    // Run the updates.
    $this->runUpdates();

    $seq_owner = $this->getSequenceOwner('pgsql_sequence_test', 'sequence_field');
    $this->assertEquals($this->connection->getPrefix() . 'pgsql_sequence_test', $seq_owner->table_name);
    $this->assertEquals('sequence_field', $seq_owner->field_name, 'Sequence is owned by the table and column.');
  }

  /**
   * Retrieves the sequence owner object.
   *
   * @return object|bool
   *   Returns the sequence owner object or bool if it does not exist.
   */
  protected function getSequenceOwner(string $table, string $field): object|bool {
    $update_sequence = \Drupal::classResolver(Update10101::class);
    $seq_name = $update_sequence->getSequenceName($table, $field);
    return \Drupal::database()->query("SELECT d.refobjid::regclass as table_name, a.attname as field_name
      FROM pg_depend d
      JOIN pg_attribute a ON a.attrelid = d.refobjid AND a.attnum = d.refobjsubid
      WHERE d.objid = :seq_name::regclass
      AND d.refobjsubid > 0
      AND d.classid = 'pg_class'::regclass", [':seq_name' => $seq_name])->fetchObject();
  }

}
