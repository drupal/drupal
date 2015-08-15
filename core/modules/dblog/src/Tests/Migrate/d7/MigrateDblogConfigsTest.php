<?php

/**
 * @file
 * Contains \Drupal\dblog\Tests\Migrate\d7\MigrateDblogConfigsTest.
 */

namespace Drupal\dblog\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to dblog.settings.yml.
 *
 * @group dblog
 */
class MigrateDblogConfigsTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_dblog_settings');
  }

  /**
   * Tests migration of dblog variables to dblog.settings.yml.
   */
  public function testDblogSettings() {
    $config = $this->config('dblog.settings');
    $this->assertIdentical(1000, $config->get('row_limit'));
  }

}
