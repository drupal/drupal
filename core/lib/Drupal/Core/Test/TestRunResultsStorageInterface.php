<?php

namespace Drupal\Core\Test;

/**
 * Interface describing a test run results storage object.
 *
 * @internal
 */
interface TestRunResultsStorageInterface {

  /**
   * Gets a new unique identifier for a test run.
   *
   * @return int|string
   *   A unique identifier.
   */
  public function createNew(): int|string;

  /**
   * Sets the test database prefix of a test run in storage.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param string $database_prefix
   *   The database prefix.
   *
   * @throws \RuntimeException
   *   If the operation failed.
   */
  public function setDatabasePrefix(TestRun $test_run, string $database_prefix): void;

  /**
   * Adds a test log entry for a test run to the storage.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param array $entry
   *   The array of the log entry elements.
   *
   * @return bool
   *   TRUE if the addition was successful, FALSE otherwise.
   */
  public function insertLogEntry(TestRun $test_run, array $entry): bool;

  /**
   * Removes the results of a test run from the storage.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   *
   * @return int
   *   The number of log entries that were removed from storage.
   */
  public function removeResults(TestRun $test_run): int;

  /**
   * Get test results for a test run, ordered by test class.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   *
   * @return array
   *   Array of results ordered by test class and message id.
   */
  public function getLogEntriesByTestClass(TestRun $test_run): array;

  /**
   * Get state information about a test run, from storage.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   *
   * @return array
   *   Array of state information, for example 'last_prefix' and 'test_class'.
   */
  public function getCurrentTestRunState(TestRun $test_run): array;

  /**
   * Prepares the test run storage.
   *
   * @param bool $keep_results
   *   If TRUE, any pre-existing storage will be preserved; if FALSE,
   *   pre-existing storage will be cleaned up.
   */
  public function buildTestingResultsEnvironment(bool $keep_results): void;

  /**
   * Checks if the test run storage is valid.
   *
   * @return bool
   *   TRUE when the storage is valid and ready for use, FALSE otherwise.
   *
   * @see ::buildTestingResultsEnvironment()
   */
  public function validateTestingResultsEnvironment(): bool;

  /**
   * Resets the test run storage.
   *
   * @return int
   *   The number of log entries that were removed from storage.
   */
  public function cleanUp(): int;

}
