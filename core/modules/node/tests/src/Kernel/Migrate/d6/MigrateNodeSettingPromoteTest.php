<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * @group migrate_drupal_6
 */
class MigrateNodeSettingPromoteTest extends MigrateDrupal6TestBase {

  protected static $modules = ['node', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
    $this->executeMigration('d6_node_setting_promote');
  }

  /**
   * Tests migration of the promote checkbox's settings.
   */
  public function testMigration() {
    $this->assertSame('Promoted to front page', BaseFieldOverride::load('node.article.promote')->label());
  }

}
