<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the user profile field migration.
 *
 * @group migrate_drupal
 */
class MigrateUserProfileFieldTest extends MigrateDrupalTestBase {

  static $modules = array('link', 'options', 'datetime');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_user_profile_field');
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
    $field_storage = entity_load('field_storage_config', 'user.profile_color');
    $this->assertEqual($field_storage->type, 'text', 'Field type is text.');
    $this->assertEqual($field_storage->cardinality, 1, 'Text field has correct cardinality');

    // Migrated a textarea.
    $field_storage = entity_load('field_storage_config', 'user.profile_biography');
    $this->assertEqual($field_storage->type, 'text_long', 'Field type is text_long.');

    // Migrated checkbox field.
    $field_storage = entity_load('field_storage_config', 'user.profile_sell_address');
    $this->assertEqual($field_storage->type, 'boolean', 'Field type is boolean.');

    // Migrated selection field.
    $field_storage = entity_load('field_storage_config', 'user.profile_sold_to');
    $this->assertEqual($field_storage->type, 'list_string', 'Field type is list_string.');
    $settings = $field_storage->getSettings();
    $this->assertEqual($settings['allowed_values'], array(
      'Pill spammers' => 'Pill spammers',
      'Fitness spammers' => 'Fitness spammers',
      'Back\slash' => 'Back\slash',
      'Forward/slash' => 'Forward/slash',
      'Dot.in.the.middle' => 'Dot.in.the.middle',
      'Faithful servant' => 'Faithful servant',
      'Anonymous donor' => 'Anonymous donor',
    ));
    $this->assertEqual($field_storage->type, 'list_string', 'Field type is list_string.');

    // Migrated list field.
    $field_storage = entity_load('field_storage_config', 'user.profile_bands');
    $this->assertEqual($field_storage->type, 'text', 'Field type is text.');
    $this->assertEqual($field_storage->cardinality, -1, 'List field has correct cardinality');

/*
    // Migrated URL field.
    $field_storage = entity_load('field_storage_config', 'user.profile_blog');
    $this->assertEqual($field_storage->type, 'link', 'Field type is link.');
*/

    // Migrated date field.
    $field_storage = entity_load('field_storage_config', 'user.profile_birthdate');
    $this->assertEqual($field_storage->type, 'datetime', 'Field type is datetime.');
    $this->assertEqual($field_storage->settings['datetime_type'], 'date');
  }

}
