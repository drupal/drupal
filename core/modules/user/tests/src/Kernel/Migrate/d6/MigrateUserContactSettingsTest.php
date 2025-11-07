<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Users contact settings migration.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateUserContactSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateUsers(FALSE);
    $this->installSchema('user', ['users_data']);
    $this->executeMigration('d6_user_contact_settings');
  }

  /**
   * Tests the Drupal6 user contact settings migration.
   */
  public function testUserContactSettings(): void {
    $user_data = \Drupal::service('user.data');
    $module = $key = 'contact';
    $uid = 2;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertSame('1', $setting);

    $uid = 8;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertSame('0', $setting);

    $uid = 15;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertNull($setting);
  }

}
