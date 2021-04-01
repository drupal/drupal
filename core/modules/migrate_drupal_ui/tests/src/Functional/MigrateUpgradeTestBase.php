<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\user\Entity\User;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

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
   * The destination site major version.
   *
   * @var string
   */
  protected $destinationSiteVersion;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate_drupal_ui');

    // Get the current major version.
    [$this->destinationSiteVersion] = explode('.', \Drupal::VERSION, 2);

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
   * Helper method that asserts text on the ID conflict form.
   *
   * @param array $entity_types
   *   An array of entity types.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function assertIdConflictForm(array $entity_types) {
    $session = $this->assertSession();
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $this->assertNotEmpty($entity_types);
    foreach ($entity_types as $entity_type) {
      $label = $entity_type_manager->getDefinition($entity_type)->getPluralLabel();
      $session->pageTextContains($label);
    }
    $session->pageTextContainsOnce('content items');
    $session->pageTextContains('There is translated content of these types:');
  }

  /**
   * Helper to assert content on the Review form.
   *
   * @param array|null $available_paths
   *   An array of modules that will be upgraded. Defaults to
   *   $this->getAvailablePaths().
   * @param array|null $missing_paths
   *   An array of modules that will not be upgraded. Defaults to
   *   $this->getMissingPaths().
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertReviewForm(array $available_paths = NULL, array $missing_paths = NULL) {
    $session = $this->assertSession();
    $session->pageTextContains('What will be upgraded?');

    $available_paths = $available_paths ?? $this->getAvailablePaths();
    $missing_paths = $missing_paths ?? $this->getMissingPaths();
    // Test the available migration paths.
    foreach ($available_paths as $available) {
      $session->elementExists('xpath', "//td[contains(@class, 'checked') and text() = '$available']");
      $session->elementNotExists('xpath', "//td[contains(@class, 'error') and text() = '$available']");
    }

    // Test the missing migration paths.
    foreach ($missing_paths as $missing) {
      $session->elementExists('xpath', "//td[contains(@class, 'error') and text() = '$missing']");
      $session->elementNotExists('xpath', "//td[contains(@class, 'checked') and text() = '$missing']");
    }

    // Test the total count of missing and available paths.
    $session->elementsCount('xpath', "//td[contains(@class, 'upgrade-analysis-report__status-icon--error')]", count($missing_paths));
    $session->elementsCount('xpath', "//td[contains(@class, 'upgrade-analysis-report__status-icon--checked')]", count($available_paths));
  }

  /**
   * Asserts the upgrade completed successfully.
   *
   * @param array $entity_counts
   *   An array of entity count, where the key is the entity type and the value
   *   is the number of the entities that should exist post migration.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertUpgrade(array $entity_counts) {
    $session = $this->assertSession();
    $session->pageTextContains(t('Congratulations, you upgraded Drupal!'));

    // Assert the count of entities after the upgrade. First, reset all the
    // statics after migration to ensure entities are loadable.
    $this->resetAll();
    // Check that the expected number of entities is the same as the actual
    // number of entities.
    $entity_definitions = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    ksort($entity_counts);
    $expected_count_keys = array_keys($entity_counts);
    sort($entity_definitions);
    $this->assertSame($expected_count_keys, $entity_definitions);

    // Assert the correct number of entities exists.
    $actual_entity_counts = [];
    foreach ($entity_definitions as $entity_type) {
      $actual_entity_counts[$entity_type] = (int) \Drupal::entityQuery($entity_type)->accessCheck(FALSE)->count()->execute();
    }
    $this->assertSame($entity_counts, $actual_entity_counts);

    $plugin_manager = \Drupal::service('plugin.manager.migration');
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
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

  /**
   * Creates an array of credentials for the Credential form.
   *
   * Before submitting to the Credential form the array must be processed by
   * BrowserTestBase::translatePostValues() before submitting.
   *
   * @return array
   *   An array of values suitable for BrowserTestBase::translatePostValues().
   *
   * @see \Drupal\migrate_drupal_ui\Form\CredentialForm
   */
  protected function getCredentials() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
    ];
    if ($version == 6) {
      $edit['d6_source_base_path'] = $this->getSourceBasePath();
    }
    else {
      $edit['source_base_path'] = $this->getSourceBasePath();
    }
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    return $edit;
  }

  /**
   * Asserts that a migrated user can login.
   */
  public function assertUserLogIn($uid, $pass) {
    $user = User::load($uid);
    $user->passRaw = $pass;
    $this->drupalLogin($user);
  }

}
