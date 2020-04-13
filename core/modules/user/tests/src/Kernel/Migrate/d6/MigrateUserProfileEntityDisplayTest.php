<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the user profile entity display migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'user_profile_field',
      'user_profile_field_instance',
      'user_profile_entity_display',
    ]);
  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileFields() {
    $display = EntityViewDisplay::load('user.user.default');

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

    // Test a checkbox field.
    $component = $display->getComponent('profile_really_really_love_mig');
    $this->assertIdentical('list_default', $component['type']);
  }

}
