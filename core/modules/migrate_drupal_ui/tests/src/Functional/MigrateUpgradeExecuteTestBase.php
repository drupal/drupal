<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing a complete upgrade via the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends MigrateUpgradeTestBase {

  use CreateTestContentEntitiesTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create content.
    $this->createContent();

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
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    // Get valid credentials.
    $edits = $this->translatePostValues($this->getCredentials());

    $this->submitForm($edits, 'Review upgrade');

    // Restart the upgrade process.
    $this->drupalGet('/upgrade');
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $this->submitForm($edits, 'Review upgrade');
    $entity_types = [
      'block_content',
      'menu_link_content',
      'file',
      'taxonomy_term',
      'user',
    ];
    $this->assertIdConflictForm($entity_types);

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the review form.
    $this->assertReviewForm();

    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCounts());

    \Drupal::service('module_installer')->install(['forum']);
    \Drupal::service('module_installer')->install(['book']);

    // Test incremental migration.
    $this->createContentPostUpgrade();

    $this->drupalGet('/upgrade');
    $session->pageTextContains("An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal $this->destinationSiteVersion. Rollbacks are not yet supported through the user interface.");
    $this->submitForm([], 'Import new configuration and content from old site');
    $this->submitForm($edits, 'Review upgrade');
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('files');
    $session->pageTextContains('There is translated content of these types:');
    $session->pageTextContainsOnce('content items');

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Run the incremental migration and check the results.
    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCountsIncremental());
  }

}
