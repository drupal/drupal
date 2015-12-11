<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d7\MigrateUserFloodTest.
 */

namespace Drupal\user\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Migrates user flood control configuration.
 *
 * @group user
 */
class MigrateUserFloodTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user']);
    $this->executeMigration('d7_user_flood');
  }

  /**
   * Tests the migration.
   */
  public function testMigration() {
    $expected = [
      'uid_only' => TRUE,
      'ip_limit' => 30,
      'ip_window' => 7200,
      'user_limit' => 22,
      'user_window' => 86400,
      '_core' => [
        'default_config_hash' => 'UYfMzeP1S8jKm9PSvxf7nQNe8DsNS-3bc2WSNNXBQWs',
      ],
    ];
    $this->assertIdentical($expected, $this->config('user.flood')->get());
  }

}
