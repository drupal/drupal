<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d6\MigrateUserProfileValuesTest.
 */

namespace Drupal\user\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * User profile values migration.
 *
 * @group user
 */
class MigrateUserProfileValuesTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array(
    'link',
    'options',
    'datetime',
    'text',
    'file',
    'image',
  );

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
      'settings' => array(
        'allowed_values' => array(
          'Pill spammers' => 'Pill spammers',
          'Fitness spammers' => 'Fitness spammers',
        )
      )
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

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_user_profile_field_instance' => array(
        array(array(1), array('user', 'user', 'fieldname')),
      ),
      'd6_user_profile_entity_display' => array(
        array(array(1), array('user', 'user', 'default', 'fieldname')),
      ),
      'd6_user_profile_entity_form_display' => array(
        array(array(1), array('user', 'user', 'default', 'fieldname')),
      ),
      'd6_user' => array(
        array(array(2), array(2)),
        array(array(8), array(8)),
        array(array(15), array(15)),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $field_data = Database::getConnection('default', 'migrate')
      ->select('profile_fields', 'u')
      ->fields('u')
      ->execute()
      ->fetchAll();
    // Create the field instances.
    foreach ($field_data as $field) {
      entity_create('field_config', array(
        'label' => $field->title,
        'description' => '',
        'field_name' => $field->name,
        'entity_type' => 'user',
        'bundle' => 'user',
        'required' => 0,
      ))->save();
    }

    // Create our users for the node authors.
    $query = Database::getConnection('default', 'migrate')->query('SELECT * FROM {users} WHERE uid NOT IN (0, 1)');
    while(($row = $query->fetchAssoc()) !== FALSE) {
      $user = entity_create('user', $row);
      $user->enforceIsNew();
      $user->save();
    }

    $migration_format = entity_load('migration', 'd6_profile_values:user');
    $this->executeMigration($migration_format);
  }

  /**
   * Tests Drupal 6 profile values to Drupal 8 migration.
   */
  public function testUserProfileValues() {
    $user = User::load(2);
    $this->assertFalse(is_null($user));
    $this->assertIdentical('red', $user->profile_color->value);
    $expected = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam nulla sapien, congue nec risus ut, adipiscing aliquet felis. Maecenas quis justo vel nulla varius euismod. Quisque metus metus, cursus sit amet sem non, bibendum vehicula elit. Cras dui nisl, eleifend at iaculis vitae, lacinia ut felis. Nullam aliquam ligula volutpat nulla consectetur accumsan. Maecenas tincidunt molestie diam, a accumsan enim fringilla sit amet. Morbi a tincidunt tellus. Donec imperdiet scelerisque porta. Sed quis sem bibendum eros congue sodales. Vivamus vel fermentum est, at rutrum orci. Nunc consectetur purus ut dolor pulvinar, ut volutpat felis congue. Cras tincidunt odio sed neque sollicitudin, vehicula tempor metus scelerisque.
EOT;
    $this->assertIdentical($expected, $user->profile_biography->value);
    $this->assertIdentical('1', $user->profile_sell_address->value);
    $this->assertIdentical('Back\slash', $user->profile_sold_to->value);
    $this->assertIdentical('AC/DC', $user->profile_bands[0]->value);
    $this->assertIdentical('Eagles', $user->profile_bands[1]->value);
    $this->assertIdentical('Elton John', $user->profile_bands[2]->value);
    $this->assertIdentical('Lemonheads', $user->profile_bands[3]->value);
    $this->assertIdentical('Rolling Stones', $user->profile_bands[4]->value);
    $this->assertIdentical('Queen', $user->profile_bands[5]->value);
    $this->assertIdentical('The White Stripes', $user->profile_bands[6]->value);
    $this->assertIdentical('1974-06-02', $user->profile_birthdate->value);

    $user = User::load(8);
    $this->assertIdentical('Forward/slash', $user->profile_sold_to->value);

    $user = User::load(15);
    $this->assertIdentical('Dot.in.the.middle', $user->profile_sold_to->value);
  }

}
