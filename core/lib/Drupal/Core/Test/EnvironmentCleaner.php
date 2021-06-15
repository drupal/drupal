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
   * The test run results storage.
   *
   * @var \Drupal\Core\Test\TestRunResultsStorageInterface
   */
  protected $testRunResultsStorage;

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
   * Constructs a test environment cleaner.
   *
   * @param string $root
   *   The path to the root of the Drupal installation.
   * @param \Drupal\Core\Database\Connection $test_database
   *   Connection to the database against which tests were run.
   * @param \Drupal\Core\Test\TestRunResultsStorageInterface $test_run_results_storage
   *   The test run results storage.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   A symfony console output object.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file_system service.
   */
  public function __construct(string $root, Connection $test_database, TestRunResultsStorageInterface $test_run_results_storage, OutputInterface $output, FileSystemInterface $file_system) {
    $this->root = $root;
    $this->testDatabase = $test_database;
    $this->testRunResultsStorage = $test_run_results_storage;
    $this->output = $output;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanEnvironment(bool $clear_results = TRUE, bool $clear_temp_directories = TRUE, bool $clear_database = TRUE): void {
    $count = 0;
    if ($clear_database) {
      $this->doCleanDatabase();
    }
    if ($clear_temp_directories) {
      $this->doCleanTemporaryDirectories();
    }
    if ($clear_results) {
      $count = $this->cleanResults();
      $this->output->write('Test results removed: ' . $count);
    }
    else {
      $this->output->write('Test results were not removed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanDatabase(): void {
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
  protected function doCleanDatabase(): int {
    /** @var \Drupal\Core\Database\Schema $schema */
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
  public function cleanTemporaryDirectories(): void {
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
  protected function doCleanTemporaryDirectories(): int {
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
  public function cleanResults(TestRun $test_run = NULL): int {
    return $test_run ? $test_run->removeResults() : $this->testRunResultsStorage->cleanUp();
  }

}
