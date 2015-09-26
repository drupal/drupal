<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeSettingPromoteTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * @group migrate_drupal_6
 */
class MigrateNodeSettingPromoteTest extends MigrateDrupal6TestBase {

  public static $modules = ['node', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
    $this->executeMigration('d6_node_setting_promote');
  }

  /**
   * Tests migration of the promote checkbox's settings.
   */
  public function testMigration() {
    $this->assertIdentical('Promoted to front page', BaseFieldOverride::load('node.article.promote')->label());
  }

}
