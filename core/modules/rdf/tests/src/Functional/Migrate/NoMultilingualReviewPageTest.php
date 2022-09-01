<?php

namespace Drupal\Tests\rdf\Functional\Migrate;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;

/**
 * Tests review page.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group rdf
 * @group legacy
 */
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('rdf') . '/tests/fixtures/drupal7.php');
  }

  /**
   * Tests that RDF is displayed in the will be upgraded list.
   */
  public function testMigrateUpgradeReviewPage() {
    $this->prepare();
    // Start the upgrade process.
    $this->submitCredentialForm();

    $session = $this->assertSession();
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Confirm that RDF will be upgraded.
    $session->elementExists('xpath', "//td[contains(@class, 'checked') and text() = 'RDF']");
    $session->elementNotExists('xpath', "//td[contains(@class, 'error') and text() = 'RDF']");
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
