<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileEntityDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests the user profile entity display migration.
 *
 * @group migrate_drupal
 */
class MigrateUserProfileEntityDisplayTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('link', 'options', 'datetime');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some fields so the data gets stored.
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_color',
      'type' => 'text',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_biography',
      'type' => 'text_long',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_sell_address',
      'type' => 'boolean',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_sold_to',
      'type' => 'list_string',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_bands',
      'type' => 'text',
      'cardinality' => -1,
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_blog',
      'type' => 'link',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_birthdate',
      'type' => 'datetime',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'profile_love_migrations',
      'type' => 'boolean',
    ))->save();

    $migration = entity_load('migration', 'd6_user_profile_entity_display');
    $dumps = array(
      $this->getDumpDirectory() . '/ProfileFields.php',
      $this->getDumpDirectory() . '/Users.php',
      $this->getDumpDirectory() . '/ProfileValues.php',
      $this->getDumpDirectory() . '/UsersRoles.php',
      $this->getDumpDirectory() . '/EventTimezones.php',
    );
    $this->prepare($migration, $dumps);
    $field_data = Database::getConnection('default', 'migrate')
      ->select('profile_fields', 'u')
      ->fields('u')
      ->execute()
      ->fetchAll();
    foreach ($field_data as $field) {
      entity_create('field_config', array(
        'label' => $field->title,
        'description' => '',
        'field_name' => $field->name,
        'entity_type' => 'user',
        'bundle' => 'user',
        'required' => 1,
      ))->save();
    }
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileFields() {
    $display = entity_get_display('user', 'user', 'default');

    // Test a text field.
    $component = $display->getComponent('profile_color');
    $this->assertIdentical($component['type'], 'text_default');

    // Test a list field.
    $component = $display->getComponent('profile_bands');
    $this->assertIdentical($component['type'], 'text_default');

    // Test a date field.
    $component = $display->getComponent('profile_birthdate');
    $this->assertIdentical($component['type'], 'datetime_default');

    // Test PROFILE_PRIVATE field is hidden.
    $this->assertNull($display->getComponent('profile_sell_address'));

    // Test PROFILE_HIDDEN field is hidden.
    $this->assertNull($display->getComponent('profile_sold_to'));
  }

}
