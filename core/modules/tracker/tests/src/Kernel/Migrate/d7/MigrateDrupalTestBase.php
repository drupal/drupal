<?php

namespace Drupal\Tests\tracker\Kernel\Migrate\d7;

use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase as CoreMigrateDrupalTestBase;
use Drupal\Tests\migrate_drupal\Traits\NodeMigrateTypeTestTrait;

/**
 * Base class for Tracker Drupal 7 migration tests.
 */
abstract class MigrateDrupalTestBase extends CoreMigrateDrupalTestBase {

  use NodeMigrateTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add a node classic migrate table to the destination site so that tests
    // run by default with the classic node migrations.
    $this->makeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '7');
    $this->loadFixture($this->getFixtureFilePath());
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal7.php';
  }

  /**
   * Executes all user migrations.
   *
   * @param bool $include_pictures
   *   (optional) If TRUE, migrates user pictures. Defaults to TRUE.
   */
  protected function migrateUsers($include_pictures = TRUE) {
    $migrations = ['d7_user_role', 'd7_user'];

    if ($include_pictures) {
      // Prepare to migrate user pictures as well.
      $this->installEntitySchema('file');
      $migrations = array_merge([
        'user_picture_field',
        'user_picture_field_instance',
      ], $migrations);
    }

    $this->executeMigrations($migrations);
  }

  /**
   * Migrates node types.
   */
  protected function migrateContentTypes() {
    $this->installConfig(['node']);
    $this->installEntitySchema('node');
    $this->executeMigration('d7_node_type');
  }

}
