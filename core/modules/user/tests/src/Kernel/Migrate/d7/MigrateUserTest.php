<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Users migration.
 *
 * @group user
 */
class MigrateUserTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'image'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Prepare to migrate user pictures as well.
    $this->installEntitySchema('file');
    $this->executeMigrations([
      'user_picture_field',
      'user_picture_field_instance',
      'd7_user_role',
      'd7_user',
    ]);
  }

  /**
   * Asserts various aspects of a user account.
   *
   * @param string $id
   *   The user ID.
   * @param string $label
   *   The username.
   * @param string $mail
   *   The user's email address.
   * @param string $password
   *   The password for this user.
   * @param int $access
   *   The last access time.
   * @param int $login
   *   The last login time.
   * @param bool $blocked
   *   Whether or not the account is blocked.
   * @param string $langcode
   *   The user account's language code.
   * @param string $init
   *   The user's initial email address.
   * @param string[] $roles
   *   Role IDs the user account is expected to have.
   * @param bool $has_picture
   *   Whether the user is expected to have a picture attached.
   */
  protected function assertEntity($id, $label, $mail, $password, $access, $login, $blocked, $langcode, $init, array $roles = [RoleInterface::AUTHENTICATED_ID], $has_picture = FALSE) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($id);
    $this->assertTrue($user instanceof UserInterface);
    $this->assertIdentical($label, $user->label());
    $this->assertIdentical($mail, $user->getEmail());
    $this->assertIdentical($access, $user->getLastAccessedTime());
    $this->assertIdentical($login, $user->getLastLoginTime());
    $this->assertIdentical($blocked, $user->isBlocked());
    // $user->getPreferredLangcode() might fallback to default language if the
    // user preferred language is not configured on the site. We just want to
    // test if the value was imported correctly.
    $this->assertIdentical($langcode, $user->langcode->value);
    $this->assertIdentical($langcode, $user->preferred_langcode->value);
    $this->assertIdentical($langcode, $user->preferred_admin_langcode->value);
    $this->assertIdentical($init, $user->getInitialEmail());
    $this->assertIdentical($roles, $user->getRoles());
    $this->assertIdentical($has_picture, !$user->user_picture->isEmpty());
    $this->assertIdentical($password, $user->getPassword());
  }

  /**
   * Tests the Drupal 7 user to Drupal 8 migration.
   */
  public function testUser() {
    $password = '$S$DGFZUE.FhrXbe4y52eC7p0ZVRGD/gOPtVctDlmC89qkujnBokAlJ';
    $this->assertEntity(2, 'Odo', 'odo@local.host', $password, '0', '0', FALSE, '', 'odo@local.host');

    // Ensure that the user can authenticate.
    $this->assertEquals(2, \Drupal::service('user.auth')->authenticate('Odo', 'a password'));
    // After authenticating the password will be rehashed because the password
    // stretching iteration count has changed from 15 in Drupal 7 to 16 in
    // Drupal 8.
    $user = User::load(2);
    $rehash = $user->getPassword();
    $this->assertNotEquals($password, $rehash);

    // Authenticate again and there should be no re-hash.
    $this->assertEquals(2, \Drupal::service('user.auth')->authenticate('Odo', 'a password'));
    $user = User::load(2);
    $this->assertEquals($rehash, $user->getPassword());
  }

}
