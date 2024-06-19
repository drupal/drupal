<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Functional\migrate_drupal\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;

/**
 * Tests migrate upgrade review page.
 *
 * @group forum
 * @group legacy
 */
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'forum',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('forum') . '/tests/fixtures/drupal6.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__;
  }

  /**
   * Tests that Forum is displayed in the will be upgraded list.
   */
  public function testMigrateUpgradeReviewPage(): void {
    $this->prepare();
    // Start the upgrade process.
    $this->submitCredentialForm();

    $session = $this->assertSession();
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Confirm that Forum will be upgraded.
    $session->elementExists('xpath', "//td[contains(@class, 'checked') and text() = 'Forum']");
    $session->elementNotExists('xpath', "//td[contains(@class, 'error') and text() = 'Forum']");
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
