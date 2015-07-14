<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileFieldInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests the user profile field instance migration.
 *
 * @group migrate_drupal
 */
class MigrateUserProfileFieldInstanceTest extends MigrateDrupal6TestBase {

  static $modules = array('field', 'link', 'options', 'datetime', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_user_profile_field' => array(
        array(array(1), array('user', 'profile_color')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->createFields();
    $this->loadDumps(array(
      'ProfileFields.php',
      'Users.php',
      'ProfileValues.php',
      'UsersRoles.php',
      'EventTimezones.php',
    ));
    $this->executeMigration('d6_user_profile_field_instance');
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

/*
    // Migrated URL field.
    $field = FieldConfig::load('user.user.profile_blog');
    $this->assertIdentical('Your blog', $field->label());
    $this->assertIdentical("Paste the full URL, $field->getDescription(), including http://, of your personal blog.");
*/

    // Migrated date field.
    $field = FieldConfig::load('user.user.profile_birthdate');
    $this->assertIdentical('Birthdate', $field->label());
    $this->assertIdentical("Enter your birth date and we'll send you a coupon.", $field->getDescription());

    // Another migrated checkbox field, with a different source visibility setting.
    $field = FieldConfig::load('user.user.profile_love_migrations');
    $this->assertIdentical('I love migrations', $field->label());
    $this->assertIdentical("If you check this box, you love migrations.", $field->getDescription());
  }

  /**
   * Helper to create fields.
   */
  protected function createFields() {
    $fields = array(
      'profile_color' => 'text',
      'profile_biography' => 'text_long',
      'profile_sell_address' => 'boolean',
      'profile_sold_to' => 'list_string',
      'profile_bands' => 'text',
      'profile_blog' => 'link',
      'profile_birthdate' => 'datetime',
      'profile_love_migrations' => 'boolean',
    );
    foreach ($fields as $name => $type) {
      entity_create('field_storage_config', array(
        'field_name' => $name,
        'entity_type' => 'user',
        'type' => $type,
      ))->save();
    }
  }

}
