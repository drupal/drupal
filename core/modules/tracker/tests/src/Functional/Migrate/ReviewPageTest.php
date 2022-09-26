<?php

namespace Drupal\Tests\tracker\Functional\Migrate;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;

/**
 * Tests Review page.
 *
 * @group tracker
 * @group legacy
 */
class ReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tracker'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('tracker') . '/tests/fixtures/drupal7.php');
  }

  /**
   * Tests the review page.
   */
  public function testMigrateUpgradeReviewPage() {
    $this->prepare();
    // Start the upgrade process.
    $this->submitCredentialForm();

    $session = $this->assertSession();
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Confirm that Tracker will be upgraded.
    $session->elementExists('xpath', "//td[contains(@class, 'checked') and text() = 'Tracker']");
    $session->elementNotExists('xpath', "//td[contains(@class, 'error') and text() = 'Tracker']");
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getIncompletePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

}
