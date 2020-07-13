<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper class for cleaning test environments.
 */
class EnvironmentCleaner implements EnvironmentCleanerInterface {

  /**
   * Path to Drupal root directory.
   *
   * @var string
   */
  protected $root;

  /**
   * Connection to the database being used for tests.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $testDatabase;

  /**
   * Connection to the database where test results are stored.
   *
   * This could be the same as $testDatabase, or it could be different.
   * run-tests.sh allows you to specify a different results database with the
   * --sqlite parameter.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $resultsDatabase;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Console output.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * Construct an environment cleaner.
   *
   * @param string $root
   *   The path to the root of the Drupal installation.
   * @param \Drupal\Core\Database\Connection $test_database
   *   Connection to the database against which tests were run.
   * @param \Drupal\Core\Database\Connection $results_database
   *   Connection to the database where test results were stored. This could be
   *   the same as $test_database, or it could be different.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   A symfony console output object.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file_system service.
   */
  public function __construct($root, Connection $test_database, Connection $results_database, OutputInterface $output, FileSystemInterface $file_system) {
    $this->root = $root;
    $this->testDatabase = $test_database;
    $this->resultsDatabase = $results_database;
    $this->output = $output;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanEnvironment($clear_results = TRUE, $clear_temp_directories = TRUE, $clear_database = TRUE) {
    $count = 0;
    if ($clear_database) {
      $this->doCleanDatabase();
    }
    if ($clear_temp_directories) {
      $this->doCleanTemporaryDirectories();
    }
    if ($clear_results) {
      $count = $this->cleanResultsTable();
      $this->output->write('Test results removed: ' . $count);
    }
    else {
      $this->output->write('Test results were not removed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanDatabase() {
    $count = $this->doCleanDatabase();
    if ($count > 0) {
      $this->output->write('Leftover tables removed: ' . $count);
    }
    else {
      $this->output->write('No leftover tables to remove.');
    }
  }

  /**
   * Performs the fixture database cleanup.
   *
   * @return int
   *   The number of tables that were removed.
   */
  protected function doCleanDatabase() {
    /* @var $schema \Drupal\Core\Database\Schema */
    $schema = $this->testDatabase->schema();
    $tables = $schema->findTables('test%');
    $count = 0;
    foreach ($tables as $table) {
      // Only drop tables which begin wih 'test' followed by digits, for example,
      // {test12345678node__body}.
      if (preg_match('/^test\d+.*/', $table, $matches)) {
        $schema->dropTable($matches[0]);
        $count++;
      }
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanTemporaryDirectories() {
    $count = $this->doCleanTemporaryDirectories();
    if ($count > 0) {
      $this->output->write('Temporary directories removed: ' . $count);
    }
    else {
      $this->output->write('No temporary directories to remove.');
    }
  }

  /**
   * Performs the cleanup of temporary test directories.
   *
   * @return int
   *   The count of temporary directories removed.
   */
  protected function doCleanTemporaryDirectories() {
    $count = 0;
    $simpletest_dir = $this->root . '/sites/simpletest';
    if (is_dir($simpletest_dir)) {
      $files = scandir($simpletest_dir);
      foreach ($files as $file) {
        if ($file[0] != '.') {
          $path = $simpletest_dir . '/' . $file;
          $this->fileSystem->deleteRecursive($path, function ($any_path) {
            @chmod($any_path, 0700);
          });
          $count++;
        }
      }
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanResultsTable($test_id = NULL) {
    $count = 0;
    if ($test_id) {
      $count = $this->resultsDatabase->query('SELECT COUNT([test_id]) FROM {simpletest_test_id} WHERE [test_id] = :test_id', [':test_id' => $test_id])->fetchField();

      $this->resultsDatabase->delete('simpletest')
        ->condition('test_id', $test_id)
        ->execute();
      $this->resultsDatabase->delete('simpletest_test_id')
        ->condition('test_id', $test_id)
        ->execute();
    }
    else {
      $count = $this->resultsDatabase->query('SELECT COUNT([test_id]) FROM {simpletest_test_id}')->fetchField();

      // Clear test results.
      $this->resultsDatabase->delete('simpletest')->execute();
      $this->resultsDatabase->delete('simpletest_test_id')->execute();
    }

    return $count;
  }

}
