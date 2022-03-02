<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\Tests\migrate_drupal\Traits\NodeMigrateTypeTestTrait;

/**
 * Base class for Drupal 6 migration tests.
 */
abstract class MigrateDrupal6TestBase extends MigrateDrupalTestBase {

  use NodeMigrateTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aggregator',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    // Add a node classic migrate table to the destination site so that tests
    // run by default with the classic node migrations.
    $this->makeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '6');
    $this->loadFixture($this->getFixtureFilePath());
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

}
