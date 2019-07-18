<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\RowCountException;
use Drupal\Core\Database\StatementInterface;
use Drupal\Tests\system\Functional\Database\FakeRecord;

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
  public function testQueryFetchDefault() {
    $records = [];
    $result = $this->connection->query('SELECT name FROM {test} WHERE age = :age', [':age' => 25]);
    $this->assertTrue($result instanceof StatementInterface, 'Result set is a Drupal statement object.');
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertTrue(is_object($record), 'Record is an object.');
      $this->assertIdentical($record->name, 'John', '25 year old is John.');
    }

    $this->assertIdentical(count($records), 1, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record to an object explicitly.
   */
  public function testQueryFetchObject() {
    $records = [];
    $result = $this->connection->query('SELECT name FROM {test} WHERE age = :age', [':age' => 25], ['fetch' => \PDO::FETCH_OBJ]);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertTrue(is_object($record), 'Record is an object.');
      $this->assertIdentical($record->name, 'John', '25 year old is John.');
    }

    $this->assertIdentical(count($records), 1, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record to an associative array explicitly.
   */
  public function testQueryFetchArray() {
    $records = [];
    $result = $this->connection->query('SELECT name FROM {test} WHERE age = :age', [':age' => 25], ['fetch' => \PDO::FETCH_ASSOC]);
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue(is_array($record), 'Record is an array.')) {
        $this->assertIdentical($record['name'], 'John', 'Record can be accessed associatively.');
      }
    }

    $this->assertIdentical(count($records), 1, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into a new instance of a custom class.
   *
   * @see \Drupal\system\Tests\Database\FakeRecord
   */
  public function testQueryFetchClass() {
    $records = [];
    $result = $this->connection->query('SELECT name FROM {test} WHERE age = :age', [':age' => 25], ['fetch' => FakeRecord::class]);
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue($record instanceof FakeRecord, 'Record is an object of class FakeRecord.')) {
        $this->assertIdentical($record->name, 'John', '25 year old is John.');
      }
    }

    $this->assertIdentical(count($records), 1, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into a new instance of a custom class.
   * The name of the class is determined from a value of the first column.
   *
   * @see \Drupal\Tests\system\Functional\Database\FakeRecord
   */
  public function testQueryFetchClasstype() {
    $records = [];
    $result = $this->connection->query('SELECT classname, name, job FROM {test_classtype} WHERE age = :age', [':age' => 26], ['fetch' => \PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE]);
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue($record instanceof FakeRecord, 'Record is an object of class FakeRecord.')) {
        $this->assertSame('Kay', $record->name, 'Kay is found.');
        $this->assertSame('Web Developer', $record->job, 'A 26 year old Web Developer.');
      }
      $this->assertFalse(isset($record->classname), 'Classname field not found, as intended.');
    }

    $this->assertCount(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into an indexed array explicitly.
   */
  public function testQueryFetchNum() {
    $records = [];
    $result = $this->connection->query('SELECT name FROM {test} WHERE age = :age', [':age' => 25], ['fetch' => \PDO::FETCH_NUM]);
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue(is_array($record), 'Record is an array.')) {
        $this->assertIdentical($record[0], 'John', 'Record can be accessed numerically.');
      }
    }

    $this->assertIdentical(count($records), 1, 'There is only one record');
  }

  /**
   * Confirms that we can fetch a record into a doubly-keyed array explicitly.
   */
  public function testQueryFetchBoth() {
    $records = [];
    $result = $this->connection->query('SELECT name FROM {test} WHERE age = :age', [':age' => 25], ['fetch' => \PDO::FETCH_BOTH]);
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue(is_array($record), 'Record is an array.')) {
        $this->assertIdentical($record[0], 'John', 'Record can be accessed numerically.');
        $this->assertIdentical($record['name'], 'John', 'Record can be accessed associatively.');
      }
    }

    $this->assertIdentical(count($records), 1, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch all records into an array explicitly.
   */
  public function testQueryFetchAllColumn() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->orderBy('name');
    $query_result = $query->execute()->fetchAll(\PDO::FETCH_COLUMN);

    $expected_result = ['George', 'John', 'Paul', 'Ringo'];
    $this->assertEqual($query_result, $expected_result, 'Returned the correct result.');
  }

  /**
   * Confirms that we can fetch an entire column of a result set at once.
   */
  public function testQueryFetchCol() {
    $result = $this->connection->query('SELECT name FROM {test} WHERE age > :age', [':age' => 25]);
    $column = $result->fetchCol();
    $this->assertIdentical(count($column), 3, 'fetchCol() returns the right number of records.');

    $result = $this->connection->query('SELECT name FROM {test} WHERE age > :age', [':age' => 25]);
    $i = 0;
    foreach ($result as $record) {
      $this->assertIdentical($record->name, $column[$i++], 'Column matches direct access.');
    }
  }

  /**
   * Tests that rowCount() throws exception on SELECT query.
   */
  public function testRowCount() {
    $result = $this->connection->query('SELECT name FROM {test}');
    try {
      $result->rowCount();
      $exception = FALSE;
    }
    catch (RowCountException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'Exception was thrown');
  }

}
