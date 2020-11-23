<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

/**
 * Tests the upgrade review form without translations.
 *
 * When using this test class, do not enable migrate_drupal_multilingual.
 */
abstract class NoMultilingualReviewPageTestBase extends MultilingualReviewPageTestBase {

  /**
   * Tests the review page when content_translation is enabled.
   */
  public function testMigrateUpgradeReviewPage() {
    $this->prepare();
    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');

    // Get valid credentials.
    $edits = $this->translatePostValues($this->getCredentials());

    $this->submitForm($edits, 'Review upgrade');

    $session = $this->assertSession();
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('taxonomy terms');
    $session->pageTextContains('There is translated content of these types:');
    $session->pageTextContainsOnce('content items');

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    $session->pageTextNotContains(t('Destination module not found for migration_provider_test'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the upgrade paths.
    $this->assertReviewForm();
  }

}
