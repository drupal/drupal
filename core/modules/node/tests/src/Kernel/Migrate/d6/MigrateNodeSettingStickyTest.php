<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * @group migrate_drupal_6
 */
class MigrateNodeSettingStickyTest extends MigrateDrupal6TestBase {

  public static $modules = ['node', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
    $this->executeMigration('d6_node_setting_sticky');
  }

  /**
   * Tests migration of the sticky checkbox's settings.
   */
  public function testMigration() {
    $this->assertIdentical('Sticky at the top of lists', BaseFieldOverride::load('node.article.sticky')->label());
  }

}
