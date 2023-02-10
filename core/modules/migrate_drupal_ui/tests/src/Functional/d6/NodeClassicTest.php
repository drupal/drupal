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
    'book',
    'statistics',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('migrate_drupal') . '/tests/fixtures/drupal6.php');
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
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
  protected function getMissingPaths() {
    return [];
  }

  /**
   * Tests node classic migration via the UI.
   */
  public function testNodeClassicUpgrade() {
    // Add a node classic migrate table to d8.
    $this->makeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '6');

    // Start the upgrade process.
    $this->submitCredentialForm();

    // Confirm there are only classic node migration map tables. This shows
    // that only the classic migration will run.
    $results = $this->nodeMigrateMapTableCount('6');
    $this->assertSame(14, $results['node']);
    $this->assertSame(0, $results['node_complete']);
  }

}
