<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

/**
 * Regression tests cases for the database layer.
 *
 * @group Database
 */
class RegressionTest extends DatabaseTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'user'];

  /**
   * Ensures that non-ASCII UTF-8 data is stored in the database properly.
   */
  public function testRegression_310447(): void {
    // That's a 255 character UTF-8 string.
    $job = str_repeat("é", 255);
    $this->connection
      ->insert('test')
      ->fields([
        'name' => $this->randomMachineName(),
        'age' => 20,
        'job' => $job,
      ])->execute();

    $from_database = $this->connection->query('SELECT [job] FROM {test} WHERE [job] = :job', [':job' => $job])->fetchField();
    $this->assertSame($job, $from_database, 'The database handles UTF-8 characters cleanly.');
  }

  /**
   * Tests the Schema::tableExists() method.
   */
  public function testDBTableExists(): void {
    $this->assertTrue($this->connection->schema()->tableExists('test'), 'Returns true for existent table.');
    $this->assertFalse($this->connection->schema()->tableExists('no_such_table'), 'Returns false for nonexistent table.');
  }

  /**
   * Tests the \Drupal\Core\Database\Schema::fieldExists() method.
   */
  public function testDBFieldExists(): void {
    $schema = $this->connection->schema();
    $this->assertTrue($schema->fieldExists('test', 'name'), 'Returns true for existent column.');
    $this->assertFalse($schema->fieldExists('test', 'no_such_column'), 'Returns false for nonexistent column.');
  }

  /**
   * Tests the Schema::indexExists() method.
   */
  public function testDBIndexExists(): void {
    $this->assertTrue($this->connection->schema()->indexExists('test', 'ages'), 'Returns true for existent index.');
    $this->assertFalse($this->connection->schema()->indexExists('test', 'no_such_index'), 'Returns false for nonexistent index.');
  }

}
