<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Users migration.
 *
 * @group migrate_drupal
 */
class MigrateUserTest extends MigrateDrupalTestBase {

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
    // Create the user profile field and instance.
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'name' => 'user_picture',
      'type' => 'image',
      'translatable' => '0',
    ))->save();
    entity_create('field_instance_config', array(
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
      $this->getDumpDirectory() . '/Drupal6FilterFormat.php',
      $this->getDumpDirectory() . '/Drupal6UserProfileFields.php',
      $this->getDumpDirectory() . '/Drupal6UserRole.php',
      $this->getDumpDirectory() . '/Drupal6User.php',
    );
    $this->loadDumps($dumps);

    $id_mappings = array(
      'd6_filter_format' => array(
        array(array(1), array('filtered_html')),
        array(array(2), array('full_html')),
        array(array(3), array('escape_html_filter')),
      ),
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

    $this->prepareIdMappings($id_mappings);

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
      $roles = array(DRUPAL_AUTHENTICATED_RID);
      $migration_role = entity_load('migration', 'd6_user_role');
      foreach ($rids as $rid) {
        $role = $migration_role->getIdMap()->lookupDestinationId(array($rid));
        $roles[] = reset($role);
      }
      // Get the user signature format.
      $migration_format = entity_load('migration', 'd6_filter_format');
      $signature_format = $migration_format->getIdMap()->lookupDestinationId(array($source->signature_format));

      $user = user_load($source->uid);
      $this->assertEqual($user->id(), $source->uid);
      $this->assertEqual($user->label(), $source->name);
      $this->assertEqual($user->getEmail(), $source->mail);
      $this->assertEqual($user->getSignature(), $source->signature);
      $this->assertEqual($user->getSignatureFormat(), reset($signature_format));
      $this->assertEqual($user->getCreatedTime(), $source->created);
      $this->assertEqual($user->getLastAccessedTime(), $source->access);
      $this->assertEqual($user->getLastLoginTime(), $source->login);
      $is_blocked = $source->status == 0;
      $this->assertEqual($user->isBlocked(), $is_blocked);
      // $user->getPreferredLangcode() might fallback to default language if the
      // user preferred language is not configured on the site. We just want to
      // test if the value was imported correctly.
      $this->assertEqual($user->preferred_langcode->value, $source->language);
      $time_zone = $source->expected_timezone ?: \Drupal::config('system.date')->get('timezone.default');
      $this->assertEqual($user->getTimeZone(), $time_zone);
      $this->assertEqual($user->getInitialEmail(), $source->init);
      $this->assertEqual($user->getRoles(), $roles);

      // We have one empty picture in the data so don't try load that.
      if (!empty($source->picture)) {
        // Test the user picture.
        $file = file_load($user->user_picture->target_id);
        $this->assertEqual($file->getFilename(), basename($source->picture));
      }

      // Use the UI to check if the password has been salted and re-hashed to
      // conform the Drupal >= 7.
      $credentials = array('name' => $source->name, 'pass' => $source->pass_plain);
      $this->drupalPostForm('user/login', $credentials, t('Log in'));
      $this->assertNoRaw(t('Sorry, unrecognized username or password. <a href="@password">Have you forgotten your password?</a>', array('@password' => url('user/password', array('query' => array('name' => $source->name))))));
      $this->drupalLogout();
    }
  }

}
