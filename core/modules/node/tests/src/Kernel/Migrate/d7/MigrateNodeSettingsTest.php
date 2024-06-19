<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to node.settings config object.
 *
 * @group node
 */
class MigrateNodeSettingsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
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
