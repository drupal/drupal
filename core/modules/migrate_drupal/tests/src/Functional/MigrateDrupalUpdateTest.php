<?php

namespace Drupal\Tests\migrate_drupal\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that migrate_drupal_multilingual is uninstalled.
 *
 * @group migrate_drupal
 */
class MigrateDrupalUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
      __DIR__ . '/../../fixtures/drupal-8.migrate-drupal-multilingual-enabled.php',
    ];
  }

  /**
   * Tests migrate_drupal_multilingual uninstallation.
   *
   * @see migrate_drupal_post_update_uninstall_multilingual()
   */
  public function testSourceFeedRequired() {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('migrate_drupal_multilingual'));
    // Run updates.
    $this->runUpdates();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('migrate_drupal_multilingual'));
  }

}
