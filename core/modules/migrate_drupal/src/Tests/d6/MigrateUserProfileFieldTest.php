<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of user profile fields.
 */
class MigrateUserProfileFieldTest extends MigrateDrupalTestBase {

  static $modules = array('link', 'options', 'datetime');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate user profile fields',
      'description'  => 'Test the user profile field migration.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_user_profile_field');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6UserProfileFields.php',
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
    $field = entity_load('field_config', 'user.profile_color');
    $this->assertEqual($field->type, 'text', 'Field type is text.');
    $this->assertEqual($field->cardinality, 1, 'Text field has correct cardinality');

    // Migrated a textarea.
    $field = entity_load('field_config', 'user.profile_biography');
    $this->assertEqual($field->type, 'text_long', 'Field type is text_long.');

    // Migrated checkbox field.
    $field = entity_load('field_config', 'user.profile_sell_address');
    $this->assertEqual($field->type, 'list_boolean', 'Field type is list_boolean.');

    // Migrated selection field.
    $field = entity_load('field_config', 'user.profile_sold_to');
    $this->assertEqual($field->type, 'list_text', 'Field type is list_text.');

    // Migrated list field.
    $field = entity_load('field_config', 'user.profile_bands');
    $this->assertEqual($field->type, 'text', 'Field type is text.');
    $this->assertEqual($field->cardinality, -1, 'List field has correct cardinality');

/*
    // Migrated URL field.
    $field = entity_load('field_config', 'user.profile_blog');
    $this->assertEqual($field->type, 'link', 'Field type is link.');
*/

    // Migrated date field.
    $field = entity_load('field_config', 'user.profile_birthdate');
    $this->assertEqual($field->type, 'datetime', 'Field type is datetime.');
  }

}
