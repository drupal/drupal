<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to node.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_node_settings');
  }

  /**
   * Tests Drupal 6 node settings to Drupal 8 migration.
   */
  public function testNodeSettings() {
    $config = $this->config('node.settings');
    $this->assertFalse($config->get('use_admin_theme'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'node.settings', $config->get());
  }

}
