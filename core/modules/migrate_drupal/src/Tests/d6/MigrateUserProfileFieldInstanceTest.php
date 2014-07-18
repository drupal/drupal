<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileFieldInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

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
    $this->prepareIdMappings($id_mappings);
    $this->createFields();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_user_profile_field_instance');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6UserProfileFields.php',
      $this->getDumpDirectory() . '/Drupal6User.php',
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
    $field = entity_load('field_instance_config', 'user.user.profile_color');
    $this->assertEqual($field->label(), 'Favorite color');
    $this->assertEqual($field->getDescription(), 'List your favorite color');

    // Migrated a textarea.
    $field = entity_load('field_instance_config', 'user.user.profile_biography');
    $this->assertEqual($field->label(), 'Biography');
    $this->assertEqual($field->getDescription(), 'Tell people a little bit about yourself');

    // Migrated checkbox field.
    $field = entity_load('field_instance_config', 'user.user.profile_sell_address');
    $this->assertEqual($field->label(), 'Sell your email address?');
    $this->assertEqual($field->getDescription(), "If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!");

    // Migrated selection field.
    $field = entity_load('field_instance_config', 'user.user.profile_sold_to');
    $this->assertEqual($field->label(), 'Sales Category');
    $this->assertEqual($field->getDescription(), "Select the sales categories to which this user's address was sold.");

    // Migrated list field.
    $field = entity_load('field_instance_config', 'user.user.profile_bands');
    $this->assertEqual($field->label(), 'Favorite bands');
    $this->assertEqual($field->getDescription(), "Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.");

/*
    // Migrated URL field.
    $field = entity_load('field_instance_config', 'user.user.profile_blog');
    $this->assertEqual($field->label(), 'Your blog');
    $this->assertEqual($field->getDescription(), "Paste the full URL, including http://, of your personal blog.");
*/

    // Migrated date field.
    $field = entity_load('field_instance_config', 'user.user.profile_birthdate');
    $this->assertEqual($field->label(), 'Birthdate');
    $this->assertEqual($field->getDescription(), "Enter your birth date and we'll send you a coupon.");

    // Another migrated checkbox field, with a different source visibility setting.
    $field = entity_load('field_instance_config', 'user.user.profile_love_migrations');
    $this->assertEqual($field->label(), 'I love migrations');
    $this->assertEqual($field->getDescription(), "If you check this box, you love migrations.");
  }

  /**
   * Helper to create fields.
   */
  protected function createFields() {
    $fields = array(
      'profile_color' => 'text',
      'profile_biography' => 'text_long',
      'profile_sell_address' => 'boolean',
      'profile_sold_to' => 'list_text',
      'profile_bands' => 'text',
      'profile_blog' => 'link',
      'profile_birthdate' => 'datetime',
      'profile_love_migrations' => 'boolean',
    );
    foreach ($fields as $name => $type) {
      entity_create('field_storage_config', array(
        'name' => $name,
        'entity_type' => 'user',
        'type' => $type,
      ))->save();
    }
  }

}
