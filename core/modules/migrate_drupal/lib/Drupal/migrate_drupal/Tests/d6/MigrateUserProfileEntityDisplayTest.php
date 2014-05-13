<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserProfileEntityDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\Dump\Drupal6UserProfileFields;

/**
 * Tests migration of user profile fields.
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
  public static function getInfo() {
    return array(
      'name'  => 'Migrate user profile entity display',
      'description'  => 'Test the user profile entity display migration.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some fields so the data gets stored.
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_color',
      'type' => 'text',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_biography',
      'type' => 'text_long',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_sell_address',
      'type' => 'list_boolean',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_sold_to',
      'type' => 'list_text',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_bands',
      'type' => 'text',
      'cardinality' => -1,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_blog',
      'type' => 'link',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_birthdate',
      'type' => 'datetime',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'user',
      'name' => 'profile_love_migrations',
      'type' => 'list_boolean',
    ))->save();
    $field_data = Drupal6UserProfileFields::getData('profile_fields');
    foreach ($field_data as $field) {
      entity_create('field_instance_config', array(
        'label' => $field['title'],
        'description' => '',
        'field_name' => $field['name'],
        'entity_type' => 'user',
        'bundle' => 'user',
        'required' => 1,
      ))->save();
    }

    $migration = entity_load('migration', 'd6_user_profile_entity_display');
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
    $display = entity_get_display('user', 'user', 'default');

    // Test a text field.
    $component = $display->getComponent('profile_color');
    $this->assertEqual($component['type'], 'text_default');

    // Test a list field.
    $component = $display->getComponent('profile_bands');
    $this->assertEqual($component['type'], 'text_default');

    // Test a date field.
    $component = $display->getComponent('profile_birthdate');
    $this->assertEqual($component['type'], 'datetime_default');

    // Test PROFILE_PRIVATE field is hidden.
    $this->assertNull($display->getComponent('profile_sell_address'));

    // Test PROFILE_HIDDEN field is hidden.
    $this->assertNull($display->getComponent('profile_sold_to'));
  }

}
