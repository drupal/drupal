<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

/**
 * Tests the upgrade review form without translations.
 *
 * When using this test class do not enable content_translation.
 */
abstract class MigrateUpgradeI18nReviewPageTestBase extends MigrateUpgradeReviewPageTestBase {

  /**
   * Tests the review page when content_translation is enabled.
   */
  public function testMigrateUpgradeReviewPage() {
    $this->prepare();
    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->drupalPostForm(NULL, [], t('Continue'));
    $this->drupalPostForm(NULL, $this->edits, t('Review upgrade'));

    $session = $this->assertSession();
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('taxonomy terms');
    $session->pageTextNotContains('There is translated content of these types:');
    $session->pageTextNotContains('custom menu links');

    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);

    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    $session->pageTextNotContains(t('Destination module not found for migration_provider_test'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the upgrade paths.
    $available_paths = $this->getAvailablePaths();
    $missing_paths = $this->getMissingPaths();
    $this->assertUpgradePaths($session, $available_paths, $missing_paths);
  }

}
