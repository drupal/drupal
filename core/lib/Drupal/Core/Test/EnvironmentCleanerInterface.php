<?php

namespace Drupal\Core\Test;

/**
 * Defines an interface for cleaning up test results and fixtures.
 *
 * This interface is marked internal. It does not imply an API.
 *
 * @todo Formalize this interface in
 *   https://www.drupal.org/project/drupal/issues/3075490 and
 *   https://www.drupal.org/project/drupal/issues/3075608
 *
 * @see https://www.drupal.org/project/drupal/issues/3075490
 * @see https://www.drupal.org/project/drupal/issues/3075608
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
   *   (optional) Whether to clear the test results database. Defaults to TRUE.
   * @param bool $clear_temp_directories
   *   (optional) Whether to clear the test site directories. Defaults to TRUE.
   * @param bool $clear_database
   *   (optional) Whether to clean up the fixture database. Defaults to TRUE.
   */
  public function cleanEnvironment($clear_results = TRUE, $clear_temp_directories = TRUE, $clear_database = TRUE);

  /**
   * Remove database entries left over in the fixture database.
   */
  public function cleanDatabase();

  /**
   * Finds all leftover fixture site directories and removes them.
   */
  public function cleanTemporaryDirectories();

  /**
   * Clears test result tables from the results database.
   *
   * @param $test_id
   *   Test ID to remove results for, or NULL to remove all results.
   *
   * @return int
   *   The number of results that were removed.
   */
  public function cleanResultsTable($test_id = NULL);

}
