<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Test\TestRun;
use Drupal\KernelTests\KernelTestBase;
use Drupal\TestTools\TestRunner\MemoryTestRunResultsStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\TestTools\TestRunner\MemoryTestRunResultsStorage.
 */
#[CoversClass(MemoryTestRunResultsStorage::class)]
#[Group('Test')]
#[RunTestsInSeparateProcesses]
class MemoryTestRunResultsStorageTest extends KernelTestBase {

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
    $this->testRunResultsStorage = new MemoryTestRunResultsStorage();
  }

  /**
   * Tests build new environment.
   */
  public function testBuildNewEnvironment(): void {
    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);
    $this->assertTrue($this->testRunResultsStorage->validateTestingResultsEnvironment());
  }

  /**
   * Tests build environment keeping existing results.
   */
  public function testBuildEnvironmentKeepingExistingResults(): void {

    // Initial build of the environment.
    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);

    $this->assertEquals(1, $this->testRunResultsStorage->createNew());
    $test_run = TestRun::get($this->testRunResultsStorage, 1);
    $this->assertEquals(1, $this->testRunResultsStorage->insertLogEntry($test_run, $this->getTestLogEntry('Test\GroundControl')));

    // Build the environment again, keeping results. Results should be kept.
    $this->testRunResultsStorage->buildTestingResultsEnvironment(TRUE);
    $this->assertTrue($this->testRunResultsStorage->validateTestingResultsEnvironment());

    $this->assertEquals(2, $this->testRunResultsStorage->createNew());
    $test_run = TestRun::get($this->testRunResultsStorage, 2);
    $this->assertEquals(2, $this->testRunResultsStorage->insertLogEntry($test_run, $this->getTestLogEntry('Test\GroundControl')));

    // Cleanup the environment.
    $this->assertEquals(2, $this->testRunResultsStorage->cleanUp());
  }

  /**
   * Tests get current test run state.
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
  }

  /**
   * Tests get log entries by test class.
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
        'message_id' => '1',
        'test_id' => '1',
        'test_class' => 'Test\GroundControl',
        'status' => 'pass',
        'message' => 'Major Tom',
        'message_group' => 'other',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
        'time' => '0',
        'exit_code' => '0',
      ],
      1 => (object) [
        'message_id' => '0',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'pass',
        'message' => 'Major Tom',
        'message_group' => 'other',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
        'time' => '0',
        'exit_code' => '0',
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
