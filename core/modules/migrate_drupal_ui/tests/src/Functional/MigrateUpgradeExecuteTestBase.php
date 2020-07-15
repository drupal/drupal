<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing a complete upgrade via the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends MigrateUpgradeTestBase {

  use CreateTestContentEntitiesTrait;

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

    // Create content.
    $this->createContent();

    // Get the current major version.
    [$this->destinationSiteVersion] = explode('.', \Drupal::VERSION, 2);
  }

  /**
   * Executes all steps of migrations upgrade.
   *
   * The upgrade is started three times. The first time is to test that
   * providing incorrect database credentials fails as expected. The second
   * time is to run the migration and assert the results. The third time is
   * to test an incremental migration, by installing the aggregator module,
   * and assert the results.
   */
  public function testMigrateUpgradeExecute() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
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
    $edits = $this->translatePostValues($edit);

    // Ensure submitting the form with invalid database credentials gives us a
    // nice warning.
    $this->drupalPostForm(NULL, [$driver . '[database]' => 'wrong'] + $edits, t('Review upgrade'));
    $session->pageTextContains('Resolve all issues below to continue the upgrade.');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    // Ensure we get errors about missing modules.
    $session->pageTextContains(t('Resolve all issues below to continue the upgrade.'));
    $session->pageTextContains(t('The no_source_module plugin must define the source_module property.'));

    // Uninstall the module causing the missing module error messages.
    $this->container->get('module_installer')->uninstall(['migration_provider_test'], TRUE);

    // Test the file sources.
    $this->drupalGet('/upgrade');
    $this->drupalPostForm(NULL, [], t('Continue'));
    if ($version == 6) {
      $paths['d6_source_base_path'] = DRUPAL_ROOT . '/wrong-path';
    }
    else {
      $paths['source_base_path'] = 'https://example.com/wrong-path';
      $paths['source_private_file_path'] = DRUPAL_ROOT . '/wrong-path';
    }
    $this->drupalPostForm(NULL, $paths + $edits, t('Review upgrade'));
    if ($version == 6) {
      $session->responseContains('Failed to read from Document root for files.');
    }
    else {
      $session->responseContains('Failed to read from Document root for public files.');
      $session->responseContains('Failed to read from Document root for private files.');
    }

    // Restart the upgrade process.
    $this->drupalGet('/upgrade');
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $entity_types = [
      'block_content',
      'menu_link_content',
      'file',
      'taxonomy_term',
      'user',
    ];
    $this->assertIdConflict($session, $entity_types);

    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);

    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the review page.
    $available_paths = $this->getAvailablePaths();
    $missing_paths = $this->getMissingPaths();
    $this->assertReviewPage($session, $available_paths, $missing_paths);

    $this->drupalPostForm(NULL, [], t('Perform upgrade'));
    $this->assertText(t('Congratulations, you upgraded Drupal!'));
    $this->assertMigrationResults($this->getEntityCounts(), $version);

    \Drupal::service('module_installer')->install(['forum']);
    \Drupal::service('module_installer')->install(['book']);

    // Test incremental migration.
    $this->createContentPostUpgrade();

    $this->drupalGet('/upgrade');
    $session->pageTextContains("An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal $this->destinationSiteVersion. Rollbacks are not yet supported through the user interface.");
    $this->drupalPostForm(NULL, [], t('Import new configuration and content from old site'));
    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('files');
    $session->pageTextContains('There is translated content of these types:');
    $session->pageTextContainsOnce('content items');

    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);

    // Run the incremental migration and check the results.
    $this->drupalPostForm(NULL, [], t('Perform upgrade'));
    $session->pageTextContains(t('Congratulations, you upgraded Drupal!'));
    $this->assertMigrationResults($this->getEntityCountsIncremental(), $version);
  }

}
