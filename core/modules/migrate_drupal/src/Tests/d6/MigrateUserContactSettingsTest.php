<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserContactSettingsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Users contact settings migration.
 *
 * @group migrate_drupal
 */
class MigrateUserContactSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('user', array('users_data'));

    $dumps = array(
      $this->getDumpDirectory() . '/Users.php',
      $this->getDumpDirectory() . '/ProfileValues.php',
      $this->getDumpDirectory() . '/UsersRoles.php',
      $this->getDumpDirectory() . '/EventTimezones.php',
    );
    $this->loadDumps($dumps);

    $id_mappings = array(
      'd6_user' => array(
        array(array(2), array(2)),
        array(array(8), array(8)),
        array(array(15), array(15)),
      ),
    );

    $this->prepareMigrations($id_mappings);

    // Migrate users.
    $migration = entity_load('migration', 'd6_user_contact_settings');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal6 user contact settings migration.
   */
  public function testUserContactSettings() {
    $user_data = \Drupal::service('user.data');
    $module = $key = 'contact';
    $uid = 2;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical('1', $setting);

    $uid = 8;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical('0', $setting);

    $uid = 15;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical(NULL, $setting);
  }

}
