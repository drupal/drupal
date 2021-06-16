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
   * Executes an upgrade and then an incremental upgrade.
   */
  public function doUpgradeAndIncremental() {
    // Start the upgrade process.
    $this->submitCredentialForm();
    $session = $this->assertSession();

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

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
    $this->submitForm($this->edits, 'Review upgrade');
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Run the incremental migration and check the results.
    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCountsIncremental());
  }

}
