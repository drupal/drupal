<?php

/**
 * @file
 * Contains \Drupal\ban\Tests\d7\MigrateBlockedIPsTest.
 */

namespace Drupal\ban\Tests\d7;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Migrate blocked IPs.
 *
 * @group ban
 */
class MigrateBlockedIPsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['ban'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('ban', ['ban_ip']);
    $this->loadDumps(['BlockedIps.php']);
    $this->executeMigration('d7_blocked_ips');
  }

  /**
   * Tests migration of blocked IPs.
   */
  public function testBlockedIPs() {
    $this->assertTrue(\Drupal::service('ban.ip_manager')->isBanned('111.111.111.111'));
  }

}
