<?php

namespace Drupal\Core\Test;

/**
 * Defines an interface for cleaning up test results and fixtures.
 *
 * This interface is marked internal. It does not imply an API.
 *
 * @todo Formalize this interface in
 *   https://www.drupal.org/project/drupal/issues/3075490
 *
 * @see https://www.drupal.org/project/drupal/issues/3075490
 *
 * @internal
 */
interface EnvironmentCleanerInterface {

  /**
   * Removes all test-related database tables and directories.
   *
   * This method removes fixture files and database entries from the system
   * under test.
   *
   * @param bool $clear_results
   *   (optional) Whether to clear the test results storage. Defaults to TRUE.
   * @param bool $clear_temp_directories
   *   (optional) Whether to clear the test site directories. Defaults to TRUE.
   * @param bool $clear_database
   *   (optional) Whether to clean up the fixture database. Defaults to TRUE.
   */
  public function cleanEnvironment(bool $clear_results = TRUE, bool $clear_temp_directories = TRUE, bool $clear_database = TRUE): void;

  /**
   * Remove database entries left over in the fixture database.
   */
  public function cleanDatabase(): void;

  /**
   * Finds all leftover fixture site directories and removes them.
   */
  public function cleanTemporaryDirectories(): void;

  /**
   * Clears test results from the results storage.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object to remove results for, or NULL to remove all
   *   results.
   *
   * @return int
   *   The number of results that were removed.
   */
  public function cleanResults(?TestRun $test_run = NULL): int;

}
