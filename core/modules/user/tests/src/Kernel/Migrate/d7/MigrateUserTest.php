<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\comment\Entity\CommentType;
use Drupal\Core\Database\Database;
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
    'language',
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
      'language',
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
   * @param int $created
   *   The user's creation time.
   * @param int $access
   *   The last access time.
   * @param int $login
   *   The last login time.
   * @param bool $blocked
   *   Whether or not the account is blocked.
   * @param string $langcode
   *   The user account's language code.
   * @param string $timezone
   *   The user account's timezone name.
   * @param string $init
   *   The user's initial email address.
   * @param string[] $roles
   *   Role IDs the user account is expected to have.
   * @param int $field_integer
   *   The value of the integer field.
   * @param bool $has_picture
   *   Whether the user is expected to have a picture attached.
   */
  protected function assertEntity($id, $label, $mail, $password, $created, $access, $login, $blocked, $langcode, $timezone, $init, $roles, $field_integer, $has_picture = FALSE) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($id);
    $this->assertTrue($user instanceof UserInterface);
    $this->assertSame($label, $user->label());
    $this->assertSame($mail, $user->getEmail());
    $this->assertSame($password, $user->getPassword());
    $this->assertSame($created, $user->getCreatedTime());
    $this->assertSame($access, $user->getLastAccessedTime());
    $this->assertSame($login, $user->getLastLoginTime());
    $this->assertNotSame($blocked, $user->isBlocked());

    // Ensure the user's langcode, preferred_langcode and
    // preferred_admin_langcode are valid.
    // $user->getPreferredLangcode() might fallback to default language if the
    // user preferred language is not configured on the site. We just want to
    // test if the value was imported correctly.
    $language_manager = $this->container->get('language_manager');
    $default_langcode = $language_manager->getDefaultLanguage()->getId();
    if ($langcode == '') {
      $this->assertSame('en', $user->langcode->value);
      $this->assertSame($default_langcode, $user->preferred_langcode->value);
      $this->assertSame($default_langcode, $user->preferred_admin_langcode->value);
    }
    elseif ($language_manager->getLanguage($langcode) === NULL) {
      $this->assertSame($default_langcode, $user->langcode->value);
      $this->assertSame($default_langcode, $user->preferred_langcode->value);
      $this->assertSame($default_langcode, $user->preferred_admin_langcode->value);
    }
    else {
      $this->assertSame($langcode, $user->langcode->value);
      $this->assertSame($langcode, $user->preferred_langcode->value);
      $this->assertSame($langcode, $user->preferred_admin_langcode->value);
    }

    $this->assertSame($timezone, $user->getTimeZone());
    $this->assertSame($init, $user->getInitialEmail());
    $this->assertSame($roles, $user->getRoles());
    $this->assertSame($has_picture, !$user->user_picture->isEmpty());
    if (!is_null($field_integer)) {
      $this->assertTrue($user->hasField('field_integer'));
      $this->assertEquals($field_integer[0], $user->field_integer->value);
    }
  }

  /**
   * Tests the Drupal 7 user to Drupal 8 migration.
   */
  public function testUser() {
    $users = Database::getConnection('default', 'migrate')
      ->select('users', 'u')
      ->fields('u')
      ->condition('uid', 1, '>')
      ->execute()
      ->fetchAll();

    foreach ($users as $source) {
      $rids = Database::getConnection('default', 'migrate')
        ->select('users_roles', 'ur')
        ->fields('ur', array('rid'))
        ->condition('ur.uid', $source->uid)
        ->execute()
        ->fetchCol();
      $roles = array(RoleInterface::AUTHENTICATED_ID);
      $id_map = $this->getMigration('d7_user_role')->getIdMap();
      foreach ($rids as $rid) {
        $role = $id_map->lookupDestinationId(array($rid));
        $roles[] = reset($role);
      }

      $field_integer = Database::getConnection('default', 'migrate')
        ->select('field_data_field_integer', 'fi')
        ->fields('fi', array('field_integer_value'))
        ->condition('fi.entity_id', $source->uid)
        ->execute()
        ->fetchCol();
      $field_integer = !empty($field_integer) ? $field_integer : NULL;

      $this->assertEntity(
        $source->uid,
        $source->name,
        $source->mail,
        $source->pass,
        $source->created,
        $source->access,
        $source->login,
        $source->status,
        $source->language,
        $source->timezone,
        $source->init,
        $roles,
        $field_integer
      );

      // Ensure that the user can authenticate.
      $this->assertEquals($source->uid, $this->container->get('user.auth')->authenticate($source->name, 'a password'));
      // After authenticating the password will be rehashed because the password
      // stretching iteration count has changed from 15 in Drupal 7 to 16 in
      // Drupal 8.
      $user = User::load($source->uid);
      $rehash = $user->getPassword();
      $this->assertNotEquals($source->pass, $rehash);

      // Authenticate again and there should be no re-hash.
      $this->assertEquals($source->uid, $this->container->get('user.auth')->authenticate($source->name, 'a password'));
      $user = User::load($source->uid);
      $this->assertEquals($rehash, $user->getPassword());
    }
  }

}
