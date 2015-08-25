<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemDateTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade date time variables to system.date config
 *
 * @group migrate_drupal_6
 */
class MigrateSystemDateTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_system_date');
  }

  /**
   * Tests migration of user variables to system_date.yml.
   */
  public function testSystemDate() {
    $config = $this->config('system.date');
    $this->assertIdentical(4, $config->get('first_day'));
    $this->assertIdentical(FALSE, $config->get('timezone.user.configurable'));
    $this->assertIdentical("Europe/Paris", $config->get('timezone.default'));
  }

}
