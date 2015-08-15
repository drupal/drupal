<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeConfigsTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to node.settings.yml.
 *
 * @group node
 */
class MigrateNodeConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_node_settings');
  }

  /**
   * Tests Drupal 6 node settings to Drupal 8 migration.
   */
  public function testNodeSettings() {
    $config = $this->config('node.settings');
    $this->assertIdentical(FALSE, $config->get('use_admin_theme'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'node.settings', $config->get());
  }

}
