<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
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
      $session->elementNotExists('xpath', "//span[contains(@class, 'error') and text() = '$available']");
    }

    // Test the missing migration paths.
    foreach ($missing_paths as $missing) {
      $session->elementExists('xpath', "//span[contains(@class, 'error') and text() = '$missing']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'checked') and text() = '$missing']");
    }

    // Test the total count of missing and available paths.
    $session->elementsCount('xpath', "//span[contains(@class, 'upgrade-analysis-report__status-icon--error')]", count($missing_paths));
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

  /**
   * Gets expected number of entities per entity after incremental migration.
   *
   * @return int[]
   *   An array of expected counts keyed by entity type ID.
   */
  abstract protected function getEntityCountsIncremental();

  /**
   * Helper method to assert the text on the 'Upgrade analysis report' page.
   *
   * @param \Drupal\Tests\WebAssert $session
   *   The current session.
   * @param array $all_available
   *   Array of modules that will be upgraded.
   * @param array $all_missing
   *   Array of modules that will not be upgraded.
   */
  protected function assertReviewPage(WebAssert $session, array $all_available, array $all_missing) {
    $this->assertText('What will be upgraded?');

    // Ensure there are no errors about the missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    $session->pageTextNotContains(t('Destination module not found for migration_provider_test'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the available migration paths.
    foreach ($all_available as $available) {
      $session->elementExists('xpath', "//span[contains(@class, 'checked') and text() = '$available']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'error') and text() = '$available']");
    }

    // Test the missing migration paths.
    foreach ($all_missing as $missing) {
      $session->elementExists('xpath', "//span[contains(@class, 'error') and text() = '$missing']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'checked') and text() = '$missing']");
    }
  }

  /**
   * Helper method that asserts text on the ID conflict form.
   *
   * @param \Drupal\Tests\WebAssert $session
   *   The current session.
   * @param $session
   *   The current session.
   */
  protected function assertIdConflict(WebAssert $session) {
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('custom blocks');
    $session->pageTextContains('custom menu links');
    $session->pageTextContains('files');
    $session->pageTextContains('taxonomy terms');
    $session->pageTextContains('users');
    $session->pageTextContains('comments');
    $session->pageTextContains('content item revisions');
    $session->pageTextContains('content items');
    $session->pageTextContains('There is translated content of these types:');
  }

  /**
   * Checks that migrations have been performed successfully.
   *
   * @param array $expected_counts
   *   The expected counts of each entity type.
   * @param int $version
   *   The Drupal version.
   */
  protected function assertMigrationResults(array $expected_counts, $version) {
    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();
    // Check that the expected number of entities is the same as the actual
    // number of entities.
    $entity_definitions = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    $expected_count_keys = array_keys($expected_counts);
    sort($entity_definitions);
    sort($expected_count_keys);
    $this->assertSame($expected_count_keys, $entity_definitions);

    // Assert the correct number of entities exist.
    foreach ($entity_definitions as $entity_type) {
      $real_count = (int) \Drupal::entityQuery($entity_type)->count()->execute();
      $expected_count = $expected_counts[$entity_type];
      $this->assertSame($expected_count, $real_count, "Found $real_count $entity_type entities, expected $expected_count.");
    }

    $plugin_manager = \Drupal::service('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);
    foreach ($all_migrations as $migration) {
      $id_map = $migration->getIdMap();
      foreach ($id_map as $source_id => $map) {
        // Convert $source_id into a keyless array so that
        // \Drupal\migrate\Plugin\migrate\id_map\Sql::getSourceHash() works as
        // expected.
        $source_id_values = array_values(unserialize($source_id));
        $row = $id_map->getRowBySource($source_id_values);
        $destination = serialize($id_map->currentDestination());
        $message = "Migration of $source_id to $destination as part of the {$migration->id()} migration. The source row status is " . $row['source_row_status'];
        // A completed migration should have maps with
        // MigrateIdMapInterface::STATUS_IGNORED or
        // MigrateIdMapInterface::STATUS_IMPORTED.
        $this->assertNotSame(MigrateIdMapInterface::STATUS_FAILED, $row['source_row_status'], $message);
        $this->assertNotSame(MigrateIdMapInterface::STATUS_NEEDS_UPDATE, $row['source_row_status'], $message);
      }
    }
  }

}
