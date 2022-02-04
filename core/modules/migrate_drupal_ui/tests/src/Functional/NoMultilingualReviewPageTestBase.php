<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

/**
 * Tests the upgrade review form without translations.
 */
abstract class NoMultilingualReviewPageTestBase extends MultilingualReviewPageTestBase {

  /**
   * Tests the review page when content_translation is enabled.
   */
  public function testMigrateUpgradeReviewPage() {
    $this->prepare();
    // Start the upgrade process.
    $this->submitCredentialForm();

    $session = $this->assertSession();
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Test the upgrade paths.
    $this->assertReviewForm();
  }

}
