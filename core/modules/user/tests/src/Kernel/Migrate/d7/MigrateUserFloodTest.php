<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates user flood control configuration.
 *
 * @group user
 */
class MigrateUserFloodTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
    $this->executeMigration('d7_user_flood');
  }

  /**
   * Tests the migration.
   */
  public function testMigration(): void {
    $expected = [
      '_core' => [
        'default_config_hash' => 'UYfMzeP1S8jKm9PSvxf7nQNe8DsNS-3bc2WSNNXBQWs',
      ],
      'uid_only' => TRUE,
      'ip_limit' => 30,
      'ip_window' => 7200,
      'user_limit' => 22,
      'user_window' => 86400,
    ];
    $this->assertSame($expected, $this->config('user.flood')->get());
  }

}
