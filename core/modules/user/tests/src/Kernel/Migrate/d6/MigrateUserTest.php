<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait;
use Drupal\user\Entity\User;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Database;
use Drupal\user\RoleInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Users migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserTest extends MigrateDrupal6TestBase {

  use FileMigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('node');
    $this->installSchema('user', ['users_data']);
    // Make sure uid 1 is created.
    user_install();

    $file = File::create(array(
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

    $file = File::create(array(
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

    $this->migrateUsers();
  }

  /**
   * Tests the Drupal6 user to Drupal 8 migration.
   */
  public function testUser() {
    $users = Database::getConnection('default', 'migrate')
      ->select('users', 'u')
      ->fields('u')
      ->condition('uid', 0, '>')
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
      $id_map = $this->getMigration('d6_user_role')->getIdMap();
      foreach ($rids as $rid) {
        $role = $id_map->lookupDestinationId(array($rid));
        $roles[] = reset($role);
      }

      /** @var \Drupal\user\UserInterface $user */
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
      $expected_timezone_name = $source->timezone_name ?: $this->config('system.date')->get('timezone.default');
      $this->assertIdentical($expected_timezone_name, $user->getTimeZone());
      $this->assertIdentical($source->init, $user->getInitialEmail());
      $this->assertIdentical($roles, $user->getRoles());

      // We have one empty picture in the data so don't try load that.
      if (!empty($source->picture)) {
        // Test the user picture.
        $file = File::load($user->user_picture->target_id);
        $this->assertIdentical(basename($source->picture), $file->getFilename());
      }
      else {
        // Ensure the user does not have a picture.
        $this->assertFalse($user->user_picture->target_id, sprintf('User %s does not have a picture', $user->id()));
      }

      // Use the API to check if the password has been salted and re-hashed to
      // conform to Drupal >= 7 for non-admin users.
      if ($user->id() != 1) {
        $this->assertTrue(\Drupal::service('password')
          ->check($source->pass_plain, $user->getPassword()));
      }
    }
    // Rollback the migration and make sure everything is deleted but uid 1.
    (new MigrateExecutable($this->migration, $this))->rollback();
    $users = Database::getConnection('default', 'migrate')
      ->select('users', 'u')
      ->fields('u', ['uid'])
      ->condition('uid', 0, '>')
      ->execute()
      ->fetchCol();
    foreach ($users as $uid) {
      $account = User::load($uid);
      if ($uid == 1) {
        $this->assertNotNull($account, 'User 1 was preserved after rollback');
      }
      else {
        $this->assertNull($account);
      }
    }
  }

}
