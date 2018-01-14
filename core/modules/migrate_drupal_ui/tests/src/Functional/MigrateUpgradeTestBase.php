<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;
use Drupal\Tests\WebAssert;

/**
 * Provides a base class for testing migration upgrades in the UI.
 */
abstract class MigrateUpgradeTestBase extends BrowserTestBase {

  use MigrationConfigurationTrait;
  use CreateTestContentEntitiesTrait;

  /**
   * Use the Standard profile to test help implementations of many core modules.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate_drupal_ui');

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param string $path
   *   Path to the dump file.
   */
  protected function loadFixture($path) {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    require $path;

    Database::setActiveConnection($default_db);
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  protected function createMigrationConnection() {
    $connection_info = Database::getConnectionInfo('default')['default'];
    if ($connection_info['driver'] === 'sqlite') {
      // Create database file in the test site's public file directory so that
      // \Drupal\simpletest\TestBase::restoreEnvironment() will delete this once
      // the test is complete.
      $file = $this->publicFilesDirectory . '/' . $this->testId . '-migrate.db.sqlite';
      touch($file);
      $connection_info['database'] = $file;
      $connection_info['prefix'] = '';
    }
    else {
      $prefix = is_array($connection_info['prefix']) ? $connection_info['prefix']['default'] : $connection_info['prefix'];
      // Simpletest uses fixed length prefixes. Create a new prefix for the
      // source database. Adding to the end of the prefix ensures that
      // \Drupal\simpletest\TestBase::restoreEnvironment() will remove the
      // additional tables.
      $connection_info['prefix'] = $prefix . '0';
    }

    Database::addConnectionInfo('migrate_drupal_ui', 'default', $connection_info);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    Database::removeConnection('migrate_drupal_ui');
    parent::tearDown();
  }

  /**
   * Transforms a nested array into a flat array suitable for BrowserTestBase::drupalPostForm().
   *
   * @param array $values
   *   A multi-dimensional form values array to convert.
   *
   * @return array
   *   The flattened $edit array suitable for BrowserTestBase::drupalPostForm().
   */
  protected function translatePostValues(array $values) {
    $edit = [];
    // The easiest and most straightforward way to translate values suitable for
    // BrowserTestBase::drupalPostForm() is to actually build the POST data string
    // and convert the resulting key/value pairs back into a flat array.
    $query = http_build_query($values);
    foreach (explode('&', $query) as $item) {
      list($key, $value) = explode('=', $item);
      $edit[urldecode($key)] = urldecode($value);
    }
    return $edit;
  }

  /**
   * Tests the displayed upgrade paths.
   *
   * @param \Drupal\Tests\WebAssert $session
   *   The web-assert session.
   * @param array $available_paths
   *   An array of modules that will be upgraded.
   * @param array $missing_paths
   *   An array of modules that will not be upgraded.
   */
  protected function assertUpgradePaths(WebAssert $session, array $available_paths, array $missing_paths) {
    // Test the available migration paths.
    foreach ($available_paths as $available) {
      $session->elementExists('xpath', "//span[contains(@class, 'checked') and text() = '$available']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'warning') and text() = '$available']");
    }

    // Test the missing migration paths.
    foreach ($missing_paths as $missing) {
      $session->elementExists('xpath', "//span[contains(@class, 'warning') and text() = '$missing']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'checked') and text() = '$missing']");
    }

    // Test the total count of missing and available paths.
    $session->elementsCount('xpath', "//span[contains(@class, 'upgrade-analysis-report__status-icon--warning')]", count($missing_paths));
    $session->elementsCount('xpath', "//span[contains(@class, 'upgrade-analysis-report__status-icon--checked')]", count($available_paths));
  }

  /**
   * Gets the source base path for the concrete test.
   *
   * @return string
   *   The source base path.
   */
  abstract protected function getSourceBasePath();

  /**
   * Gets the expected number of entities per entity type after migration.
   *
   * @return int[]
   *   An array of expected counts keyed by entity type ID.
   */
  abstract protected function getEntityCounts();

  /**
   * Gets the available upgrade paths.
   *
   * @return string[]
   *   An array of available upgrade paths.
   */
  abstract protected function getAvailablePaths();

  /**
   * Gets the missing upgrade paths.
   *
   * @return string[]
   *   An array of missing upgrade paths.
   */
  abstract protected function getMissingPaths();

}
