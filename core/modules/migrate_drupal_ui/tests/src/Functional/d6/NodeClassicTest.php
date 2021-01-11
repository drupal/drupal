<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\Tests\migrate_drupal\Traits\NodeMigrateTypeTestTrait;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests the classic node migration runs.
 *
 * The classic node migration will run and not the complete node migration
 * when there is a pre-existing classic node migrate map table.
 *
 * @group migrate_drupal_ui
 */
class NodeClassicTest extends MigrateUpgradeExecuteTestBase {

  use NodeMigrateTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'config_translation',
    'migrate_drupal_ui',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'statistics',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal6.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
  }

  /**
   * Tests ID Conflict form.
   */
  public function testMigrateUpgradeExecute() {
    // Add a node classic migrate table to d8.
    $this->makeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '6');

    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    // Get valid credentials.
    $edits = $this->translatePostValues($this->getCredentials());

    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    // When the Credential form is submitted the migrate map tables are created.
    $this->submitForm($edits, 'Review upgrade');

    // Confirm there are only classic node migration map tables. This shows
    // that only the classic migration will run.
    $results = $this->nodeMigrateMapTableCount('6');
    $this->assertSame(14, $results['node']);
    $this->assertSame(0, $results['node_complete']);
  }

}
