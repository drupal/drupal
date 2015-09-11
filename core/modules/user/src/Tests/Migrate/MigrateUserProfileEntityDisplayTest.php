<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserProfileEntityDisplayTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Tests the user profile entity display migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('link', 'options', 'datetime', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('user_profile_field');
    $this->executeMigration('user_profile_field_instance');
    $this->executeMigration('user_profile_entity_display');
  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileFields() {
    $display = entity_get_display('user', 'user', 'default');

    // Test a text field.
    $component = $display->getComponent('profile_color');
    $this->assertIdentical('text_default', $component['type']);

    // Test a list field.
    $component = $display->getComponent('profile_bands');
    $this->assertIdentical('text_default', $component['type']);

    // Test a date field.
    $component = $display->getComponent('profile_birthdate');
    $this->assertIdentical('datetime_default', $component['type']);

    // Test PROFILE_PRIVATE field is hidden.
    $this->assertNull($display->getComponent('profile_sell_address'));

    // Test PROFILE_HIDDEN field is hidden.
    $this->assertNull($display->getComponent('profile_sold_to'));
  }

}
