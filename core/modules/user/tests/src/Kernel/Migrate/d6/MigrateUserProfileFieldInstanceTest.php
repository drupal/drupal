<?php

declare(strict_types=1);

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
  public function testUserProfileFields(): void {
    // Migrated a text field.
    $field = FieldConfig::load('user.user.profile_color');
    $this->assertSame('Favorite color', $field->label());
    $this->assertSame('List your favorite color', $field->getDescription());

    // Migrated a textarea.
    $field = FieldConfig::load('user.user.profile_biography');
    $this->assertSame('Biography', $field->label());
    $this->assertSame('Tell people a little bit about yourself', $field->getDescription());

    // Migrated checkbox field.
    $field = FieldConfig::load('user.user.profile_sell_address');
    $this->assertSame('Sell your email address?', $field->label());
    $this->assertSame("If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!", $field->getDescription());

    // Migrated selection field.
    $field = FieldConfig::load('user.user.profile_sold_to');
    $this->assertSame('Sales Category', $field->label());
    $this->assertSame("Select the sales categories to which this user's address was sold.", $field->getDescription());

    // Migrated list field.
    $field = FieldConfig::load('user.user.profile_bands');
    $this->assertSame('Favorite bands', $field->label());
    $this->assertSame("Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.", $field->getDescription());

    // Migrated URL field.
    $field = FieldConfig::load('user.user.profile_blog');
    $this->assertSame('Blog', $field->label());
    $this->assertSame("Paste the full URL, including http://, of your personal blog.", $field->getDescription());

    // Migrated date field.
    $field = FieldConfig::load('user.user.profile_birthdate');
    $this->assertSame('Birthdate', $field->label());
    $this->assertSame("Enter your birth date and we'll send you a coupon.", $field->getDescription());

    // Another migrated checkbox field, with a different source visibility
    // setting.
    $field = FieldConfig::load('user.user.profile_really_really_love_mig');
    $this->assertSame('I really, really, really love migrations', $field->label());
    $this->assertSame("If you check this box, you love migrations.", $field->getDescription());
  }

}
