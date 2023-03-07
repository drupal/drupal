<?php

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestRun;
use Drupal\Core\Test\SimpletestTestRunResultsStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Test\SimpletestTestRunResultsStorage
 * @group Test
 */
class SimpletestTestRunResultsStorageTest extends KernelTestBase {

  /**
   * The database connection for testing.
   *
   * NOTE: this is the connection to the fixture database to allow testing the
   * storage class, NOT the database where actual tests results are stored.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The test run results storage.
   *
   * @var \Drupal\Core\Test\TestRunResultsStorageInterface
   */
  protected $testRunResultsStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->connection = Database::getConnection();
    $this->testRunResultsStorage = new SimpletestTestRunResultsStorage($this->connection);
  }

  /**
   * @covers ::buildTestingResultsEnvironment
   * @covers ::validateTestingResultsEnvironment
   */
  public function testBuildNewEnvironment(): void {
    $schema = $this->connection->schema();

    $this->assertFalse($schema->tableExists('simpletest'));
    $this->assertFalse($schema->tableExists('simpletest_test_id'));
    $this->assertFalse($this->testRunResultsStorage->validateTestingResultsEnvironment());

    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);

    $this->assertTrue($schema->tableExists('simpletest'));
    $this->assertTrue($schema->tableExists('simpletest_test_id'));
    $this->assertTrue($this->testRunResultsStorage->validateTestingResultsEnvironment());
  }

  /**
   * @covers ::buildTestingResultsEnvironment
   * @covers ::validateTestingResultsEnvironment
   * @covers ::createNew
   * @covers ::insertLogEntry
   * @covers ::cleanUp
   */
  public function testBuildEnvironmentKeepingExistingResults(): void {
    $schema = $this->connection->schema();

    // Initial build of the environment.
    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);

    $this->assertEquals(1, $this->testRunResultsStorage->createNew());
    $test_run = TestRun::get($this->testRunResultsStorage, 1);
    $this->assertEquals(1, $this->testRunResultsStorage->insertLogEntry($test_run, $this->getTestLogEntry('Test\GroundControl')));
    $this->assertEquals(1, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    // Build the environment again, keeping results. Results should be kept.
    $this->testRunResultsStorage->buildTestingResultsEnvironment(TRUE);
    $this->assertTrue($schema->tableExists('simpletest'));
    $this->assertTrue($schema->tableExists('simpletest_test_id'));
    $this->assertTrue($this->testRunResultsStorage->validateTestingResultsEnvironment());
    $this->assertEquals(1, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    $this->assertEquals(2, $this->testRunResultsStorage->createNew());
    $test_run = TestRun::get($this->testRunResultsStorage, 2);
    $this->assertEquals(2, $this->testRunResultsStorage->insertLogEntry($test_run, $this->getTestLogEntry('Test\GroundControl')));
    $this->assertEquals(2, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(2, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    // Cleanup the environment.
    $this->assertEquals(2, $this->testRunResultsStorage->cleanUp());
    $this->assertEquals(0, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(0, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());
  }

  /**
   * @covers ::buildTestingResultsEnvironment
   * @covers ::createNew
   * @covers ::insertLogEntry
   * @covers ::setDatabasePrefix
   * @covers ::removeResults
   */
  public function testGetCurrentTestRunState(): void {
    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);

    $this->assertEquals(1, $this->testRunResultsStorage->createNew());
    $test_run_1 = TestRun::get($this->testRunResultsStorage, 1);
    $this->testRunResultsStorage->setDatabasePrefix($test_run_1, 'oddity1234');
    $this->assertEquals(1, $this->testRunResultsStorage->insertLogEntry($test_run_1, $this->getTestLogEntry('Test\GroundControl')));
    $this->assertEquals([
      'db_prefix' => 'oddity1234',
      'test_class' => 'Test\GroundControl',
    ], $this->testRunResultsStorage->getCurrentTestRunState($test_run_1));

    // Add another test run.
    $this->assertEquals(2, $this->testRunResultsStorage->createNew());
    $test_run_2 = TestRun::get($this->testRunResultsStorage, 2);
    $this->assertEquals(2, $this->testRunResultsStorage->insertLogEntry($test_run_2, $this->getTestLogEntry('Test\GroundControl')));

    // Remove test run 1 results.
    $this->assertEquals(1, $this->testRunResultsStorage->removeResults($test_run_1));
    $this->assertEquals(1, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());
  }

  /**
   * @covers ::buildTestingResultsEnvironment
   * @covers ::createNew
   * @covers ::insertLogEntry
   * @covers ::setDatabasePrefix
   * @covers ::getLogEntriesByTestClass
   */
  public function testGetLogEntriesByTestClass(): void {
    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);

    $this->assertEquals(1, $this->testRunResultsStorage->createNew());
    $test_run = TestRun::get($this->testRunResultsStorage, 1);
    $this->testRunResultsStorage->setDatabasePrefix($test_run, 'oddity1234');
    $this->assertEquals(1, $this->testRunResultsStorage->insertLogEntry($test_run, $this->getTestLogEntry('Test\PlanetEarth')));
    $this->assertEquals(2, $this->testRunResultsStorage->insertLogEntry($test_run, $this->getTestLogEntry('Test\GroundControl')));
    $this->assertEquals([
      0 => (object) [
        'message_id' => 2,
        'test_id' => 1,
        'test_class' => 'Test\GroundControl',
        'status' => 'pass',
        'message' => 'Major Tom',
        'message_group' => 'other',
        'function' => 'Unknown',
        'line' => 0,
        'file' => 'Unknown',
      ],
      1 => (object) [
        'message_id' => 1,
        'test_id' => 1,
        'test_class' => 'Test\PlanetEarth',
        'status' => 'pass',
        'message' => 'Major Tom',
        'message_group' => 'other',
        'function' => 'Unknown',
        'line' => 0,
        'file' => 'Unknown',
      ],
    ], $this->testRunResultsStorage->getLogEntriesByTestClass($test_run));
  }

  /**
   * Returns a sample test run log entry.
   *
   * @param string $test_class
   *   The test class.
   *
   * @return string[]
   *   An array with the elements to be logged.
   */
  protected function getTestLogEntry(string $test_class): array {
    return [
      'test_class' => $test_class,
      'status' => 'pass',
      'message' => 'Major Tom',
      'message_group' => 'other',
    ];
  }

}
