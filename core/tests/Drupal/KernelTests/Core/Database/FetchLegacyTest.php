<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the Database system's various fetch capabilities.
 *
 * We get timeout errors if we try to run too many tests at once.
 *
 * @group Database
 */
class FetchLegacyTest extends DatabaseTestBase {

  /**
   * Confirms that we can fetch a record to an object explicitly.
   */
  #[IgnoreDeprecations]
  public function testQueryFetchObject(): void {
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in query() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in prepareStatement() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in execute() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => \PDO::FETCH_OBJ]);
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
  #[IgnoreDeprecations]
  public function testQueryFetchArray(): void {
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in query() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in prepareStatement() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in execute() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => \PDO::FETCH_ASSOC]);
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertIsArray($record);
      $this->assertArrayHasKey('name', $record);
      $this->assertSame('John', $record['name']);
    }

    $this->assertCount(1, $records, 'There is only one record.');
  }

  /**
   * Confirms that we can fetch a record into an indexed array explicitly.
   */
  #[IgnoreDeprecations]
  public function testQueryFetchNum(): void {
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in query() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in prepareStatement() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $this->expectDeprecation("Passing the 'fetch' key as an integer to \$options in execute() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $records = [];
    $result = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25], ['fetch' => \PDO::FETCH_NUM]);
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
  #[IgnoreDeprecations]
  public function testQueryFetchAllColumn(): void {
    $this->expectDeprecation("Passing the \$mode argument as an integer to fetchAll() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->orderBy('name');
    $query_result = $query->execute()->fetchAll(\PDO::FETCH_COLUMN);

    $expected_result = ['George', 'John', 'Paul', 'Ringo'];
    $this->assertEquals($expected_result, $query_result, 'Returned the correct result.');
  }

  /**
   * Tests ::fetchAllAssoc().
   */
  #[IgnoreDeprecations]
  public function testQueryFetchAllAssoc(): void {
    $this->expectDeprecation("Passing the \$fetch argument as an integer to fetchAllAssoc() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338");
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
    $result = $statement->fetchAllAssoc('job', \PDO::FETCH_ASSOC);
    $this->assertSame($expected_result, $result);

    $statement = $this->connection->query('SELECT * FROM {test} WHERE [age] > :age', [':age' => 26]);
    $result = $statement->fetchAllAssoc('job', \PDO::FETCH_OBJ);
    $this->assertEquals((object) $expected_result['Singer'], $result['Singer']);
    $this->assertEquals((object) $expected_result['Drummer'], $result['Drummer']);
  }

  /**
   * Confirms that we can fetch a single column value.
   */
  #[IgnoreDeprecations]
  public function testQueryFetchColumn(): void {
    $statement = $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    $statement->setFetchMode(\PDO::FETCH_COLUMN, 0);
    $this->assertSame('John', $statement->fetch());
  }

  /**
   * Confirms that an out of range index throws an error.
   */
  #[IgnoreDeprecations]
  public function testQueryFetchColumnOutOfRange(): void {
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('Invalid column index');
    $statement = $this->connection
      ->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => 25]);
    $statement->setFetchMode(\PDO::FETCH_COLUMN, 200);
    $statement->fetch();
  }

}
