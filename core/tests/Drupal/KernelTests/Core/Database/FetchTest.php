<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\RowCountException;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\StatementPrefetchIterator;
use Drupal\Tests\system\Functional\Database\FakeRecord;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the Database system's various fetch capabilities.
 *
 * We get timeout errors if we try to run too many tests at once.
 *
 * @group Database
 */
class FetchTest extends DatabaseTestBase {

  /**
   * Confirms that we can fetch a record properly in default object mode.
   */
  public function testQueryFetchDefault(): void {
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    $this->assertInstanceOf(StatementInterface::class, $result);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertIsObject($record);
      $this->assertSame('John', $record->name);
    }

    $this->assertCount(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a single column value.
   */
  public function testQueryFetchColumn(): void {
    $statement = $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    $statement->setFetchMode(FetchAs::Column, 0);
    $this->assertSame('John', $statement->fetch());
  }

  /**
   * Confirms that an out of range index throws an error.
   */
  public function testQueryFetchColumnOutOfRange(): void {
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('Invalid column index');
    $statement = $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    $statement->setFetchMode(FetchAs::Column, 200);
    $statement->fetch();
  }

  /**
   * Confirms that we can fetch a record to an object explicitly.
   */
  public function testQueryFetchObject(): void {
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => FetchAs::Object]);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertIsObject($record);
      $this->assertSame('John', $record->name);
    }

    $this->assertCount(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record to an associative array explicitly.
   */
  public function testQueryFetchArray(): void {
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => FetchAs::Associative]);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertIsArray($record);
      $this->assertArrayHasKey('name', $record);
      $this->assertSame('John', $record['name']);
    }

    $this->assertCount(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into a new instance of a custom class.
   *
   * @see \Drupal\system\Tests\Database\FakeRecord
   */
  public function testQueryFetchClass(): void {
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => FakeRecord::class]);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertInstanceOf(FakeRecord::class, $record);
      $this->assertSame('John', $record->name);
    }

    $this->assertCount(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into a class using fetchObject.
   *
   * @see \Drupal\Tests\system\Functional\Database\FakeRecord
   * @see \Drupal\Core\Database\StatementPrefetch::fetchObject
   */
  public function testQueryFetchObjectClass(): void {
    $records = 0;
    $query = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    while ($result = $query->fetchObject(FakeRecord::class, [1])) {
      $records += 1;
      $this->assertInstanceOf(FakeRecord::class, $result);
      $this->assertSame('John', $result->name, '25 year old is John.');
      $this->assertSame(1, $result->fakeArg, 'The record has received an argument through its constructor.');
    }
    $this->assertSame(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into a class without constructor args.
   *
   * @see \Drupal\Tests\system\Functional\Database\FakeRecord
   * @see \Drupal\Core\Database\StatementPrefetch::fetchObject
   */
  public function testQueryFetchObjectClassNoConstructorArgs(): void {
    $records = 0;
    $query = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    while ($result = $query->fetchObject(FakeRecord::class)) {
      $records += 1;
      $this->assertInstanceOf(FakeRecord::class, $result);
      $this->assertSame('John', $result->name);
      $this->assertSame(0, $result->fakeArg);
    }
    $this->assertSame(1, $records);
  }

  /**
   * Confirms that we can fetch a record into an indexed array explicitly.
   */
  public function testQueryFetchNum(): void {
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => FetchAs::List]);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertIsArray($record);
      $this->assertArrayHasKey(0, $record);
      $this->assertSame('John', $record[0]);
    }

    $this->assertCount(1, $records, 'There is only one record');
  }

  /**
   * Confirms that we can fetch all records into an array explicitly.
   */
  public function testQueryFetchAllColumn(): void {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->orderBy('name');
    $query_result = $query->execute()->fetchAll(FetchAs::Column);

    $expected_result = ['George', 'John', 'Paul', 'Ringo'];
    $this->assertEquals($expected_result, $query_result, 'Returned the correct result.');
  }

  /**
   * Confirms that we can fetch an entire column of a result set at once.
   */
  public function testQueryFetchCol(): void {
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25]);
    $column = $result->fetchCol();
    $this->assertCount(3, $column, 'fetchCol() returns the right number of records.');

    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25]);
    $i = 0;
    foreach ($result as $record) {
      $this->assertSame($column[$i++], $record->name, 'Column matches direct access.');
    }
  }

  /**
   * Tests ::fetchCol() for edge values returned.
   */
  public function testQueryFetchColEdgeCases(): void {
    $this->connection->insert('test_null')
      ->fields([
        'name' => 'Foo',
        'age' => 0,
      ])
      ->execute();

    $this->connection->insert('test_null')
      ->fields([
        'name' => 'Bar',
        'age' => NULL,
      ])
      ->execute();

    $this->connection->insert('test_null')
      ->fields([
        'name' => 'Qux',
        'age' => (int) FALSE,
      ])
      ->execute();

    $statement = $this->connection->select('test_null')
      ->fields('test_null', ['age'])
      ->orderBy('id')
      ->execute();

    $this->assertSame(['0', NULL, '0'], $statement->fetchCol());

    // Additional fetch returns FALSE since the result set is finished.
    $this->assertFalse($statement->fetchField());
  }

  /**
   * Confirms that an out of range index in fetchCol() throws an error.
   */
  public function testQueryFetchColIndexOutOfRange(): void {
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('Invalid column index');
    $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])
      ->fetchCol(200);
  }

  /**
   * Confirms empty result set prevails on out of range index in fetchCol().
   */
  public function testQueryFetchColIndexOutOfRangeOnEmptyResultSet(): void {
    $this->assertSame([], $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 255])
      ->fetchCol(200));
  }

  /**
   * Tests ::fetchAllAssoc().
   */
  public function testQueryFetchAllAssoc(): void {
    $expected_result = [
      "Singer" => [
        "id" => "2",
        "name" => "George",
        "age" => "27",
        "job" => "Singer",
      ],
      "Drummer" => [
        "id" => "3",
        "name" => "Ringo",
        "age" => "28",
        "job" => "Drummer",
      ],
    ];

    $statement = $this->connection->query('SELECT * FROM {test} WHERE [age] > :age', [':age' => 26]);
    $result = $statement->fetchAllAssoc('job', FetchAs::Associative);
    $this->assertSame($expected_result, $result);

    $statement = $this->connection->query('SELECT * FROM {test} WHERE [age] > :age', [':age' => 26]);
    $result = $statement->fetchAllAssoc('job', FetchAs::Object);
    $this->assertEquals((object) $expected_result['Singer'], $result['Singer']);
    $this->assertEquals((object) $expected_result['Drummer'], $result['Drummer']);
  }

  /**
   * Tests ::fetchField().
   */
  public function testQueryFetchField(): void {
    $this->connection->insert('test')
      ->fields([
        'name' => 'Foo',
        'age' => 0,
        'job' => 'Dummy',
      ])
      ->execute();

    $this->connection->insert('test')
      ->fields([
        'name' => 'Kurt',
        'age' => 27,
        'job' => 'Singer',
      ])
      ->execute();

    $expectedResults = ['25', '27', '28', '26', '0', '27'];

    $statement = $this->connection->select('test')
      ->fields('test', ['age'])
      ->orderBy('id')
      ->execute();

    $actualResults = [];
    while (TRUE) {
      $result = $statement->fetchField();
      if ($result === FALSE) {
        break;
      }
      $this->assertIsNumeric($result);
      $actualResults[] = $result;
    }

    $this->assertSame($expectedResults, $actualResults);
  }

  /**
   * Tests ::fetchField() for edge values returned.
   */
  public function testQueryFetchFieldEdgeCases(): void {
    $this->connection->insert('test_null')
      ->fields([
        'name' => 'Foo',
        'age' => 0,
      ])
      ->execute();

    $this->connection->insert('test_null')
      ->fields([
        'name' => 'Bar',
        'age' => NULL,
      ])
      ->execute();

    $this->connection->insert('test_null')
      ->fields([
        'name' => 'Qux',
        'age' => (int) FALSE,
      ])
      ->execute();

    $statement = $this->connection->select('test_null')
      ->fields('test_null', ['age'])
      ->orderBy('id')
      ->execute();

    // First fetch returns '0' since an existing value is always a string.
    $this->assertSame('0', $statement->fetchField());

    // Second fetch returns NULL since NULL was inserted.
    $this->assertNull($statement->fetchField());

    // Third fetch returns '0' since a FALSE bool cast to int was inserted.
    $this->assertSame('0', $statement->fetchField());

    // Fourth fetch returns FALSE since no row was available.
    $this->assertFalse($statement->fetchField());
  }

  /**
   * Confirms that an out of range index in fetchField() throws an error.
   */
  public function testQueryFetchFieldIndexOutOfRange(): void {
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('Invalid column index');
    $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25])
      ->fetchField(200);
  }

  /**
   * Confirms empty result set prevails on out of range index in fetchField().
   */
  public function testQueryFetchFieldIndexOutOfRangeOnEmptyResultSet(): void {
    $this->assertFalse($this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 255])
      ->fetchField(200));
  }

  /**
   * Tests that rowCount() throws exception on SELECT query.
   */
  public function testRowCount(): void {
    $result = $this->connection->query('SELECT [name] FROM {test}');
    try {
      $result->rowCount();
      $exception = FALSE;
    }
    catch (RowCountException) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'Exception was thrown');
  }

  /**
   * Confirms deprecation of StatementPrefetchIterator::fetchColumn().
   */
  #[IgnoreDeprecations]
  public function testLegacyFetchColumn(): void {
    $statement = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    if (!$statement instanceof StatementPrefetchIterator) {
      $this->markTestSkipped('This test is for StatementPrefetchIterator statements only.');
    }

    $this->expectDeprecation('Drupal\Core\Database\StatementPrefetchIterator::fetchColumn() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use ::fetchField() instead. See https://www.drupal.org/node/3490312');
    $statement->fetchColumn();
  }

}
