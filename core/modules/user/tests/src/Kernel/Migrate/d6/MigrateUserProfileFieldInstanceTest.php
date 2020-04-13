<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the user profile field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'user_profile_field',
      'user_profile_field_instance',
    ]);
  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileFields() {
    // Migrated a text field.
    $field = FieldConfig::load('user.user.profile_color');
    $this->assertIdentical('Favorite color', $field->label());
    $this->assertIdentical('List your favorite color', $field->getDescription());

    // Migrated a textarea.
    $field = FieldConfig::load('user.user.profile_biography');
    $this->assertIdentical('Biography', $field->label());
    $this->assertIdentical('Tell people a little bit about yourself', $field->getDescription());

    // Migrated checkbox field.
    $field = FieldConfig::load('user.user.profile_sell_address');
    $this->assertIdentical('Sell your email address?', $field->label());
    $this->assertIdentical("If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!", $field->getDescription());

    // Migrated selection field.
    $field = FieldConfig::load('user.user.profile_sold_to');
    $this->assertIdentical('Sales Category', $field->label());
    $this->assertIdentical("Select the sales categories to which this user's address was sold.", $field->getDescription());

    // Migrated list field.
    $field = FieldConfig::load('user.user.profile_bands');
    $this->assertIdentical('Favorite bands', $field->label());
    $this->assertIdentical("Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.", $field->getDescription());

    // Migrated URL field.
    $field = FieldConfig::load('user.user.profile_blog');
    $this->assertIdentical('Blog', $field->label());
    $this->assertIdentical("Paste the full URL, including http://, of your personal blog.", $field->getDescription());

    // Migrated date field.
    $field = FieldConfig::load('user.user.profile_birthdate');
    $this->assertIdentical('Birthdate', $field->label());
    $this->assertIdentical("Enter your birth date and we'll send you a coupon.", $field->getDescription());

    // Another migrated checkbox field, with a different source visibility setting.
    $field = FieldConfig::load('user.user.profile_really_really_love_mig');
    $this->assertIdentical('I really, really, really love migrations', $field->label());
    $this->assertIdentical("If you check this box, you love migrations.", $field->getDescription());
  }

}
