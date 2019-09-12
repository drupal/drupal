<?php

namespace Drupal\simpletest;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Test\EnvironmentCleaner;

/**
 * Uses containerized services to perform post-test cleanup.
 */
class EnvironmentCleanerService extends EnvironmentCleaner {

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Default cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDefault;

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
   * @param \Drupal\Core\StringTranslation\TranslationInterface|null $translation
   *   (optional) The translation service. If none is supplied, this class will
   *   attempt to discover one using \Drupal.
   */
  public function __construct($root, Connection $test_database, Connection $results_database, MessengerInterface $messenger, TranslationInterface $translation, ConfigFactory $config, CacheBackendInterface $cache_default, FileSystem $file_system) {
    $this->root = $root;
    $this->testDatabase = $test_database;
    $this->resultsDatabase = $results_database;
    $this->messenger = $messenger;
    $this->translation = $translation;
    $this->configFactory = $config;
    $this->cacheDefault = $cache_default;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanEnvironment($clear_results = TRUE, $clear_temp_directories = TRUE, $clear_database = TRUE) {
    $results_removed = 0;
    $clear_results = $this->configFactory->get('simpletest.settings')->get('clear_results');

    if ($clear_database) {
      $this->cleanDatabase();
    }
    if ($clear_temp_directories) {
      $this->cleanTemporaryDirectories();
    }
    if ($clear_results) {
      $results_removed = $this->cleanResultsTable();
    }
    $this->cacheDefault->delete('simpletest');
    $this->cacheDefault->delete('simpletest_phpunit');

    if ($clear_results) {
      $this->messenger->addMessage($this->translation->formatPlural($results_removed, 'Removed 1 test result.', 'Removed @count test results.'));
    }
    else {
      $this->messenger->addMessage($this->translation->translate('Clear results is disabled and the test results table will not be cleared.'), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanDatabase() {
    $tables_removed = $this->doCleanDatabase();
    if ($tables_removed > 0) {
      $this->messenger->addMessage($this->translation->formatPlural($tables_removed, 'Removed 1 leftover table.', 'Removed @count leftover tables.'));
    }
    else {
      $this->messenger->addMessage($this->translation->translate('No leftover tables to remove.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanTemporaryDirectories() {
    $directories_removed = $this->doCleanTemporaryDirectories();
    if ($directories_removed > 0) {
      $this->messenger->addMessage($this->translation->formatPlural($directories_removed, 'Removed 1 temporary directory.', 'Removed @count temporary directories.'));
    }
    else {
      $this->messenger->addMessage($this->translation->translate('No temporary directories to remove.'));
    }
  }

}
