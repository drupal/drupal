<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Tests the user profile field migration.
 *
 * @group migrate_drupal
 */
class MigrateUserProfileFieldTest extends MigrateDrupal6TestBase {

  static $modules = array('link', 'options', 'datetime');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_user_profile_field');
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
    $field_storage = FieldStorageConfig::load('user.profile_color');
    $this->assertIdentical('text', $field_storage->getType(), 'Field type is text.');
    $this->assertIdentical(1, $field_storage->getCardinality(), 'Text field has correct cardinality');

    // Migrated a textarea.
    $field_storage = FieldStorageConfig::load('user.profile_biography');
    $this->assertIdentical('text_long', $field_storage->getType(), 'Field type is text_long.');

    // Migrated checkbox field.
    $field_storage = FieldStorageConfig::load('user.profile_sell_address');
    $this->assertIdentical('boolean', $field_storage->getType(), 'Field type is boolean.');

    // Migrated selection field.
    $field_storage = FieldStorageConfig::load('user.profile_sold_to');
    $this->assertIdentical('list_string', $field_storage->getType(), 'Field type is list_string.');
    $settings = $field_storage->getSettings();
    $this->assertIdentical($settings['allowed_values'], array(
      'Pill spammers' => 'Pill spammers',
      'Fitness spammers' => 'Fitness spammers',
      'Back\slash' => 'Back\slash',
      'Forward/slash' => 'Forward/slash',
      'Dot.in.the.middle' => 'Dot.in.the.middle',
      'Faithful servant' => 'Faithful servant',
      'Anonymous donor' => 'Anonymous donor',
    ));
    $this->assertIdentical('list_string', $field_storage->getType(), 'Field type is list_string.');

    // Migrated list field.
    $field_storage = FieldStorageConfig::load('user.profile_bands');
    $this->assertIdentical('text', $field_storage->getType(), 'Field type is text.');
    $this->assertIdentical(-1, $field_storage->getCardinality(), 'List field has correct cardinality');

/*
    // Migrated URL field.
    $field_storage = FieldStorageConfig::load('user.profile_blog');
    $this->assertIdentical('link', $field_storage->getType(), 'Field type is link.');
*/

    // Migrated date field.
    $field_storage = FieldStorageConfig::load('user.profile_birthdate');
    $this->assertIdentical('datetime', $field_storage->getType(), 'Field type is datetime.');
    $this->assertIdentical('date', $field_storage->getSettings()['datetime_type']);
  }

}
