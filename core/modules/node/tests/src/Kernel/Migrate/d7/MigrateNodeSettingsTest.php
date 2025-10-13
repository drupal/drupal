<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to node.settings config object.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class MigrateNodeSettingsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_node_settings');
  }

  /**
   * Tests migration of node variables to node.settings config object.
   */
  public function testAggregatorSettings(): void {
    $config = $this->config('node.settings');
    $this->assertEquals(1, $config->get('use_admin_theme'));
  }

}
