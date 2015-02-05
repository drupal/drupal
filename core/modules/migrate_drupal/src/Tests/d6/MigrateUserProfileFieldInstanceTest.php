<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileFieldInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the user profile field instance migration.
 *
 * @group migrate_drupal
 */
class MigrateUserProfileFieldInstanceTest extends MigrateDrupalTestBase {

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
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_user_profile_field_instance');
    $dumps = array(
      $this->getDumpDirectory() . '/ProfileFields.php',
      $this->getDumpDirectory() . '/Users.php',
      $this->getDumpDirectory() . '/ProfileValues.php',
      $this->getDumpDirectory() . '/UsersRoles.php',
      $this->getDumpDirectory() . '/EventTimezones.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileFields() {
    // Migrated a text field.
    $field = FieldConfig::load('user.user.profile_color');
    $this->assertIdentical($field->label(), 'Favorite color');
    $this->assertIdentical($field->getDescription(), 'List your favorite color');

    // Migrated a textarea.
    $field = FieldConfig::load('user.user.profile_biography');
    $this->assertIdentical($field->label(), 'Biography');
    $this->assertIdentical($field->getDescription(), 'Tell people a little bit about yourself');

    // Migrated checkbox field.
    $field = FieldConfig::load('user.user.profile_sell_address');
    $this->assertIdentical($field->label(), 'Sell your email address?');
    $this->assertIdentical($field->getDescription(), "If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!");

    // Migrated selection field.
    $field = FieldConfig::load('user.user.profile_sold_to');
    $this->assertIdentical($field->label(), 'Sales Category');
    $this->assertIdentical($field->getDescription(), "Select the sales categories to which this user's address was sold.");

    // Migrated list field.
    $field = FieldConfig::load('user.user.profile_bands');
    $this->assertIdentical($field->label(), 'Favorite bands');
    $this->assertIdentical($field->getDescription(), "Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.");

/*
    // Migrated URL field.
    $field = FieldConfig::load('user.user.profile_blog');
    $this->assertIdentical($field->label(), 'Your blog');
    $this->assertIdentical($field->getDescription(), "Paste the full URL, including http://, of your personal blog.");
*/

    // Migrated date field.
    $field = FieldConfig::load('user.user.profile_birthdate');
    $this->assertIdentical($field->label(), 'Birthdate');
    $this->assertIdentical($field->getDescription(), "Enter your birth date and we'll send you a coupon.");

    // Another migrated checkbox field, with a different source visibility setting.
    $field = FieldConfig::load('user.user.profile_love_migrations');
    $this->assertIdentical($field->label(), 'I love migrations');
    $this->assertIdentical($field->getDescription(), "If you check this box, you love migrations.");
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
