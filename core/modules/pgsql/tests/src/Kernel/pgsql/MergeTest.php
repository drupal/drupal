<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Merge;
use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the native MERGE implementation of the PostgreSQL driver.
 *
 * The generic merge behavior is covered by the core MergeTest, which also
 * runs against this driver. This test covers what is specific to the native
 * MERGE implementation: a merge executes as a single MERGE statement, its
 * status code is derived from RETURNING merge_action(), and queries that
 * cannot be expressed as a native MERGE fall back to the generic emulation.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class MergeTest extends DriverSpecificDatabaseTestBase {

  /**
   * Executes a merge-like callback and returns the logged queries.
   */
  protected function getLoggedQueries(callable $callback): array {
    // Warm the table information cache outside of the logged region, so the
    // log contains only the queries of the merge itself.
    $this->connection->schema()->queryTableInformation('test_people');
    Database::startLog('merge_test');
    $callback();
    $queries = Database::getLog('merge_test');
    return array_column($queries, 'query');
  }

  /**
   * Tests the SQL string of a native merge query.
   */
  public function testMergeSqlString(): void {
    $sql = (string) $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->fields(['age' => 31, 'name' => 'Tiffany'])
      ->useDefaults(['job'])
      ->expression('age', '[age] + :age', [':age' => 4]);

    $this->assertStringContainsString('MERGE INTO {test_people} USING (SELECT 1) AS drupal_merge_source ON', $sql);
    $this->assertStringContainsString('WHEN MATCHED THEN UPDATE SET', $sql);
    $this->assertStringContainsString('WHEN NOT MATCHED THEN INSERT', $sql);
    $this->assertStringContainsString('DEFAULT', $sql);
    $this->assertStringContainsString('RETURNING merge_action()', $sql);
  }

  /**
   * Tests that a merge executes as a single native MERGE statement.
   */
  public function testMergeExecutesSingleStatement(): void {
    // Merge-insert: the key does not exist yet.
    $result = NULL;
    $queries = $this->getLoggedQueries(function () use (&$result) {
      $result = $this->connection->merge('test_people')
        ->key('job', 'Presenter')
        ->fields(['age' => 31, 'name' => 'Tiffany'])
        ->execute();
    });
    $this->assertSame(Merge::STATUS_INSERT, $result);
    $this->assertCount(1, $queries);
    $this->assertStringContainsString('MERGE INTO', $queries[0]);

    // Merge-update: the same statement now matches the existing row.
    $queries = $this->getLoggedQueries(function () use (&$result) {
      $result = $this->connection->merge('test_people')
        ->key('job', 'Presenter')
        ->fields(['age' => 32, 'name' => 'Tiffany'])
        ->execute();
    });
    $this->assertSame(Merge::STATUS_UPDATE, $result);
    $this->assertCount(1, $queries);
    $this->assertStringContainsString('MERGE INTO', $queries[0]);

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Presenter'])->fetch();
    $this->assertEquals('Tiffany', $person->name);
    $this->assertEquals(32, $person->age);
  }

  /**
   * Tests a merge that matches a row but has nothing to update.
   */
  public function testMergeMatchedWithoutUpdate(): void {
    $result = FALSE;
    $queries = $this->getLoggedQueries(function () use (&$result) {
      $result = $this->connection->merge('test_people')
        ->key('job', 'Speaker')
        ->execute();
    });
    // Without update fields the MERGE has no WHEN MATCHED clause, so no
    // action is performed and no status is returned.
    $this->assertNull($result);
    $this->assertCount(1, $queries);
    $this->assertStringContainsString('MERGE INTO', $queries[0]);

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEquals('Meredith', $person->name);
    $this->assertEquals(30, $person->age);
  }

  /**
   * Tests a native merge combining literal updates and expressions.
   */
  public function testMergeUpdateExpression(): void {
    $result = $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->fields(['name' => 'Tiffany'])
      ->insertFields(['age' => 31])
      ->expression('age', '[age] + :age', [':age' => 4])
      ->execute();

    $this->assertSame(Merge::STATUS_UPDATE, $result);
    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEquals('Tiffany', $person->name);
    $this->assertEquals(34, $person->age);
  }

  /**
   * Tests a native merge with a select subquery as expression.
   */
  public function testMergeSelectExpression(): void {
    $select = $this->connection->select('test', 't')->condition('t.name', 'Ringo');
    $select->addExpression('[t].[age]');

    $result = $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->insertFields(['name' => 'Tiffany'])
      ->expression('age', $select)
      ->execute();

    $this->assertSame(Merge::STATUS_UPDATE, $result);
    $age = $this->connection->query('SELECT [age] FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetchField();
    // Ringo's age in the sample data of the test table.
    $this->assertEquals(28, $age);
  }

  /**
   * Tests a native merge into blob fields.
   */
  public function testMergeBlob(): void {
    $this->connection->schema()->createTable('test_blob_merge', [
      'fields' => [
        'name' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE],
        'data' => ['type' => 'blob', 'size' => 'big'],
      ],
      'primary key' => ['name'],
    ]);

    $fetch_blob = function () {
      $data = $this->connection->query('SELECT [data] FROM {test_blob_merge} WHERE [name] = :name', [':name' => 'trademark'])->fetchField();
      return is_resource($data) ? stream_get_contents($data) : $data;
    };

    $binary = "Drupal \x00 The \x01 dries \xff his \xcc\xdd tears";
    $result = $this->connection->merge('test_blob_merge')
      ->key('name', 'trademark')
      ->fields(['data' => $binary])
      ->execute();
    $this->assertSame(Merge::STATUS_INSERT, $result);
    $this->assertSame($binary, $fetch_blob());

    $binary = "The \x00 quick \xcc brown \xff fox";
    $result = $this->connection->merge('test_blob_merge')
      ->key('name', 'trademark')
      ->fields(['data' => $binary])
      ->execute();
    $this->assertSame(Merge::STATUS_UPDATE, $result);
    $this->assertSame($binary, $fetch_blob());
  }

  /**
   * Tests that inserting into a serial field uses the generic emulation.
   */
  public function testMergeSerialFieldFallback(): void {
    $result = NULL;
    $queries = $this->getLoggedQueries(function () use (&$result) {
      $result = $this->connection->merge('test')
        ->key('id', 999)
        ->fields(['name' => 'David', 'age' => 40, 'job' => 'Developer'])
        ->execute();
    });
    $this->assertSame(Merge::STATUS_INSERT, $result);
    // The generic emulation issues a SELECT followed by an INSERT.
    $this->assertGreaterThan(1, count($queries));
    foreach ($queries as $query) {
      $this->assertStringNotContainsString('MERGE INTO', $query);
    }

    // The insert path of the generic emulation has synchronized the sequence
    // of the serial field, so a regular insert does not collide with the
    // explicitly inserted id.
    $id = $this->connection->insert('test')
      ->fields(['name' => 'Sylvia', 'age' => 41, 'job' => 'Editor'])
      ->execute();
    $this->assertEquals(1000, $id);
  }

  /**
   * Tests that a merge without insert fields uses the generic emulation.
   */
  public function testMergeWithoutInsertFieldsFallback(): void {
    $result = NULL;
    $queries = $this->getLoggedQueries(function () use (&$result) {
      $result = $this->connection->merge('test_people')
        ->condition('job', 'Speaker')
        ->updateFields(['age' => 31])
        ->execute();
    });
    // Without insert fields a WHEN NOT MATCHED THEN INSERT clause cannot be
    // generated, so the generic emulation is used.
    $this->assertSame(Merge::STATUS_UPDATE, $result);
    foreach ($queries as $query) {
      $this->assertStringNotContainsString('MERGE INTO', $query);
    }

    $age = $this->connection->query('SELECT [age] FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetchField();
    $this->assertEquals(31, $age);
  }

}
