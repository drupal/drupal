<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\user\Entity\User;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\user\RoleInterface;

/**
 * Users migration.
 *
 * @group migrate_drupal
 */
class MigrateUserTest extends MigrateDrupal6TestBase {

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

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    // Create the user profile field and instance.
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'type' => 'image',
      'translatable' => '0',
    ))->save();
    entity_create('field_config', array(
      'label' => 'User Picture',
      'description' => '',
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
      'required' => 0,
    ))->save();

    $file = entity_create('file', array(
      'fid' => 2,
      'uid' => 2,
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    $file->enforceIsNew();
    file_put_contents($file->getFileUri(), file_get_contents('core/modules/simpletest/files/image-1.png'));
    $file->save();

    $file = entity_create('file', array(
      'fid' => 8,
      'uid' => 8,
      'filename' => 'image-test.png',
      'uri' => "public://image-test.png",
      'filemime' => 'image/png',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    $file->enforceIsNew();
    file_put_contents($file->getFileUri(), file_get_contents('core/modules/simpletest/files/image-2.jpg'));
    $file->save();

    // Load database dumps to provide source data.
    $dumps = array(
      $this->getDumpDirectory() . '/Filters.php',
      $this->getDumpDirectory() . '/FilterFormats.php',
      $this->getDumpDirectory() . '/Variable.php',
      $this->getDumpDirectory() . '/ProfileFields.php',
      $this->getDumpDirectory() . '/Permission.php',
      $this->getDumpDirectory() . '/Role.php',
      $this->getDumpDirectory() . '/Users.php',
      $this->getDumpDirectory() . '/ProfileValues.php',
      $this->getDumpDirectory() . '/UsersRoles.php',
      $this->getDumpDirectory() . '/EventTimezones.php',
    );
    $this->loadDumps($dumps);

    $id_mappings = array(
      'd6_user_role' => array(
        array(array(1), array('anonymous user')),
        array(array(2), array('authenticated user')),
        array(array(3), array('migrate test role 1')),
        array(array(4), array('migrate test role 2')),
        array(array(5), array('migrate test role 3')),
      ),
      'd6_user_picture_entity_display' => array(
        array(array(1), array('user', 'user', 'default', 'user_picture')),
      ),
      'd6_user_picture_entity_form_display' => array(
        array(array(1), array('user', 'user', 'default', 'user_picture')),
      ),
      'd6_user_picture_file' => array(
        array(array(2), array(2)),
        array(array(8), array(8)),
      ),
    );

    $this->prepareMigrations($id_mappings);

    // Migrate users.
    $migration = entity_load('migration', 'd6_user');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal6 user to Drupal 8 migration.
   */
  public function testUser() {
    $users = Database::getConnection('default', 'migrate')
      ->select('users', 'u')
      ->fields('u')
      ->execute()
      ->fetchAll();

    foreach ($users as $source) {
      // Get roles directly from the source.
      $rids = Database::getConnection('default', 'migrate')
        ->select('users_roles', 'ur')
        ->fields('ur', array('rid'))
        ->condition('ur.uid', $source->uid)
        ->execute()
        ->fetchCol();
      $roles = array(RoleInterface::AUTHENTICATED_ID);
      $migration_role = entity_load('migration', 'd6_user_role');
      foreach ($rids as $rid) {
        $role = $migration_role->getIdMap()->lookupDestinationId(array($rid));
        $roles[] = reset($role);
      }

      $user = User::load($source->uid);
      $this->assertIdentical($source->uid, $user->id());
      $this->assertIdentical($source->name, $user->label());
      $this->assertIdentical($source->mail, $user->getEmail());
      $this->assertIdentical($source->created, $user->getCreatedTime());
      $this->assertIdentical($source->access, $user->getLastAccessedTime());
      $this->assertIdentical($source->login, $user->getLastLoginTime());
      $is_blocked = $source->status == 0;
      $this->assertIdentical($is_blocked, $user->isBlocked());
      // $user->getPreferredLangcode() might fallback to default language if the
      // user preferred language is not configured on the site. We just want to
      // test if the value was imported correctly.
      $this->assertIdentical($source->language, $user->preferred_langcode->value);
      $time_zone = $source->expected_timezone ?: $this->config('system.date')->get('timezone.default');
      $this->assertIdentical($time_zone, $user->getTimeZone());
      $this->assertIdentical($source->init, $user->getInitialEmail());
      $this->assertIdentical($roles, $user->getRoles());

      // We have one empty picture in the data so don't try load that.
      if (!empty($source->picture)) {
        // Test the user picture.
        $file = File::load($user->user_picture->target_id);
        $this->assertIdentical(basename($source->picture), $file->getFilename());
      }

      // Use the API to check if the password has been salted and re-hashed to
      // conform the Drupal >= 7.
      $this->assertTrue(\Drupal::service('password')->check($source->pass_plain, $user));
    }
  }

}
