<?php

/**
 * @file
 * Contains \Drupal\dblog\Tests\Migrate\d6\MigrateDblogConfigsTest.
 */

namespace Drupal\dblog\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to dblog.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateDblogConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['dblog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_dblog_settings');
  }

  /**
   * Tests migration of dblog variables to dblog.settings.yml.
   */
  public function testBookSettings() {
    $config = $this->config('dblog.settings');
    $this->assertIdentical(10000, $config->get('row_limit'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'dblog.settings', $config->get());
  }

}
