<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Migrate Node Setting Promote.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateNodeSettingPromoteTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
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
  public function testMigration(): void {
    $this->assertSame('Promoted to front page', BaseFieldOverride::load('node.article.promote')->label());
  }

}
