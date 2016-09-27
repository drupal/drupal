<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\comment\Entity\CommentType;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
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
  public static $modules = [
    'comment',
    'datetime',
    'file',
    'image',
    'link',
    'node',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Prepare to migrate user pictures as well.
    $this->installEntitySchema('file');
    $this->createType('page');
    $this->createType('article');
    $this->createType('blog');
    $this->createType('book');
    $this->createType('forum');
    $this->createType('test_content_type');
    Vocabulary::create(['vid' => 'test_vocabulary'])->save();
    $this->executeMigrations([
      'user_picture_field',
      'user_picture_field_instance',
      'd7_user_role',
      'd7_field',
      'd7_field_instance',
      'd7_user',
    ]);
  }

  /**
   * Creates a node type with a corresponding comment type.
   *
   * @param string $id
   *   The node type ID.
   */
  protected function createType($id) {
    NodeType::create([
      'type' => $id,
      'label' => $this->randomString(),
    ])->save();

    CommentType::create([
      'id' => 'comment_node_' . $id,
      'label' => $this->randomString(),
      'target_entity_type_id' => 'node',
    ])->save();
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
   * @param int $field_integer
   *   The value of the integer field.
   */
  protected function assertEntity($id, $label, $mail, $password, $access, $login, $blocked, $langcode, $init, array $roles = [RoleInterface::AUTHENTICATED_ID], $has_picture = FALSE, $field_integer = NULL) {
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
    if (!is_null($field_integer)) {
      $this->assertTrue($user->hasField('field_integer'));
      $this->assertEquals($field_integer, $user->field_integer->value);
    }
  }

  /**
   * Tests the Drupal 7 user to Drupal 8 migration.
   */
  public function testUser() {
    $password = '$S$DGFZUE.FhrXbe4y52eC7p0ZVRGD/gOPtVctDlmC89qkujnBokAlJ';
    $this->assertEntity(2, 'Odo', 'odo@local.host', $password, '0', '0', FALSE, 'en', 'odo@local.host', [RoleInterface::AUTHENTICATED_ID], FALSE, 99);

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
