<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
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
  public function testRepeatedInsertStatementReuse() {
    $num_records_before = $this->connection->select('test')->countQuery()->execute()->fetchField();

    $sql = "INSERT INTO {test} ([name], [age]) VALUES (:name, :age)";
    $args = [
      ':name' => 'Larry',
      ':age' => '30',
    ];
    $options = [
      'return' => Database::RETURN_STATEMENT,
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
   * Tests accessing deprecated properties.
   *
   * @group legacy
   */
  public function testGetDeprecatedProperties(): void {
    $statement = $this->connection->prepareStatement('SELECT * FROM {test}', []);
    $this->expectDeprecation('%s$dbh should not be accessed in drupal:9.3.0 and will error in drupal:10.0.0. Use $this->connection instead. See https://www.drupal.org/node/3186368');
    $this->assertNotNull($statement->dbh);
    $this->expectDeprecation('%s$allowRowCount should not be accessed in drupal:9.3.0 and will error in drupal:10.0.0. Use $this->rowCountEnabled instead. See https://www.drupal.org/node/3186368');
    $this->assertFalse($statement->allowRowCount);
  }

  /**
   * Tests writing deprecated properties.
   *
   * @group legacy
   */
  public function testSetDeprecatedProperties(): void {
    $statement = $this->connection->prepareStatement('UPDATE {test} SET [age] = :age', []);
    $this->expectDeprecation('%s$allowRowCount should not be written in drupal:9.3.0 and will error in drupal:10.0.0. Enable row counting by passing the appropriate argument to the constructor instead. See https://www.drupal.org/node/3186368');
    $statement->allowRowCount = TRUE;
    $statement->execute([':age' => 12]);
    $this->assertEquals(4, $statement->rowCount());
  }

}
