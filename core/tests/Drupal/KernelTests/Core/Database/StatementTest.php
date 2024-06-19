<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\StatementInterface;

/**
 * Tests the Statement classes.
 *
 * @group Database
 */
class StatementTest extends DatabaseTestBase {

  /**
   * Tests that a prepared statement object can be reused for multiple inserts.
   */
  public function testRepeatedInsertStatementReuse(): void {
    $num_records_before = $this->connection->select('test')->countQuery()->execute()->fetchField();

    $sql = "INSERT INTO {test} ([name], [age]) VALUES (:name, :age)";
    $args = [
      ':name' => 'Larry',
      ':age' => '30',
    ];
    $options = [
      'allow_square_brackets' => FALSE,
    ];

    $stmt = $this->connection->prepareStatement($sql, $options);
    $this->assertInstanceOf(StatementInterface::class, $stmt);
    $this->assertTrue($stmt->execute($args, $options));

    // We should be able to specify values in any order if named.
    $args = [
      ':age' => '31',
      ':name' => 'Curly',
    ];
    $this->assertTrue($stmt->execute($args, $options));

    $num_records_after = $this->connection->select('test')->countQuery()->execute()->fetchField();
    $this->assertEquals($num_records_before + 2, $num_records_after);
    $this->assertSame('30', $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Larry'])->fetchField());
    $this->assertSame('31', $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField());
  }

  /**
   * Tests statement fetching after a full traversal.
   */
  public function testIteratedStatementFetch(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    foreach ($statement as $row) {
      $this->assertNotNull($row);
    }

    $this->assertSame([], $statement->fetchAll());
    $this->assertSame([], $statement->fetchAllKeyed());
    $this->assertSame([], $statement->fetchAllAssoc('age'));
    $this->assertSame([], $statement->fetchCol());

    $this->assertFalse($statement->fetch());
    $this->assertFalse($statement->fetchObject());
    $this->assertFalse($statement->fetchAssoc());
    $this->assertFalse($statement->fetchField());
  }

  /**
   * Tests statement fetchAll after a partial traversal.
   */
  public function testPartiallyIteratedStatementFetchAll(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    for ($i = 0; $i < 2; $i++) {
      $statement->fetch();
    }

    $expected = [
      0 => (object) [
        "id" => "3",
        "name" => "Ringo",
        "age" => "28",
        "job" => "Drummer",
      ],
      1 => (object) [
        "id" => "4",
        "name" => "Paul",
        "age" => "26",
        "job" => "Songwriter",
      ],
    ];

    $this->assertEquals($expected, $statement->fetchAll());
    $this->assertSame([], $statement->fetchAll());
  }

  /**
   * Tests statement fetchAllKeyed after a partial traversal.
   */
  public function testPartiallyIteratedStatementFetchAllKeyed(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    for ($i = 0; $i < 2; $i++) {
      $statement->fetch();
    }

    $expected = [
      "3" => "Ringo",
      "4" => "Paul",
    ];

    $this->assertSame($expected, $statement->fetchAllKeyed());
    $this->assertSame([], $statement->fetchAllKeyed());
  }

  /**
   * Tests statement fetchAllAssoc after a partial traversal.
   */
  public function testPartiallyIteratedStatementFetchAllAssoc(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    for ($i = 0; $i < 2; $i++) {
      $statement->fetch();
    }

    $expected = [
      "28" => (object) [
        "id" => "3",
        "name" => "Ringo",
        "age" => "28",
        "job" => "Drummer",
      ],
      "26" => (object) [
        "id" => "4",
        "name" => "Paul",
        "age" => "26",
        "job" => "Songwriter",
      ],
    ];

    $this->assertEquals($expected, $statement->fetchAllAssoc('age'));
    $this->assertSame([], $statement->fetchAllAssoc('age'));
  }

  /**
   * Tests statement fetchCol after a partial traversal.
   */
  public function testPartiallyIteratedStatementFetchCol(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    for ($i = 0; $i < 2; $i++) {
      $statement->fetch();
    }

    $expected = ["3", "4"];

    $this->assertSame($expected, $statement->fetchCol());
    $this->assertSame([], $statement->fetchCol());
  }

  /**
   * Tests statement rewinding.
   */
  public function testStatementRewind(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    foreach ($statement as $row) {
      $this->assertNotNull($row);
    }

    // Trying to iterate through the same statement again should fail.
    $this->expectError();
    $this->expectErrorMessage('Attempted rewinding a StatementInterface object when fetching has already started. Refactor your code to avoid rewinding statement objects.');
    foreach ($statement as $row) {
      $this->assertNotNull($row);
    }
  }

  /**
   * Tests empty statement rewinding.
   */
  public function testEmptyStatementRewind(): void {
    $statement = $this->connection->query('SELECT * FROM {test} WHERE 1 = 0');

    $count = 0;

    foreach ($statement as $row) {
      $count++;
    }
    foreach ($statement as $row) {
      $count++;
    }

    $this->assertEquals(0, $count);
  }

  /**
   * Tests counting a statement twice.
   *
   * We need to use iterator_count() and not assertCount() since the latter
   * would rewind the statement twice anyway.
   */
  public function testStatementCountTwice(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');
    $rowCount = iterator_count($statement);
    $this->assertSame(4, $rowCount);

    $this->expectError();
    $this->expectErrorMessage('Attempted rewinding a StatementInterface object when fetching has already started. Refactor your code to avoid rewinding statement objects.');
    $rowCount = iterator_count($statement);
  }

  /**
   * Tests statement with no results counting twice.
   *
   * We need to use iterator_count() and not assertCount() since the latter
   * would rewind the statement twice anyway.
   */
  public function testEmptyStatementCountTwice(): void {
    $statement = $this->connection->query('SELECT * FROM {test} WHERE 1 = 0');
    $rowCount = iterator_count($statement);
    $this->assertSame(0, $rowCount);
    $rowCount = iterator_count($statement);
    $this->assertSame(0, $rowCount);
  }

  /**
   * Tests a follow-on iteration on a statement using foreach.
   */
  public function testStatementForeachTwice(): void {
    $statement = $this->connection->query('SELECT * FROM {test}');

    $count = 0;
    foreach ($statement as $row) {
      $count++;
      $this->assertNotNull($row);
      if ($count > 2) {
        break;
      }
    }
    $this->assertSame(3, $count);

    // Restart iterating through the same statement. The foreach loop will try
    // rewinding the statement which should fail, and the counter should not be
    // increased.
    $this->expectError();
    $this->expectErrorMessage('Attempted rewinding a StatementInterface object when fetching has already started. Refactor your code to avoid rewinding statement objects.');
    foreach ($statement as $row) {
      // No-op.
    }
  }

}
