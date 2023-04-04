<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Core\Database\Database;
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
  protected static $modules = [
    'comment',
    'content_translation',
    'datetime',
    'datetime_range',
    'image',
    'language',
    'link',
    'menu_ui',
    'node',
    'phpass',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigration('language');
    $this->migrateFields();
    $this->migrateUsers();
    $this->executeMigrations([
      'd7_entity_translation_settings',
      'd7_user_entity_translation',
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
   * @param int $created
   *   The user's creation time.
   * @param int $access
   *   The last access time.
   * @param int $login
   *   The last login time.
   * @param bool $blocked
   *   Whether or not the account is blocked.
   * @param string $entity_langcode
   *   The user entity language code.
   * @param string $prefered_langcode
   *   The user prefered language code.
   * @param string $timezone
   *   The user account's timezone name.
   * @param string $init
   *   The user's initial email address.
   * @param string[] $roles
   *   Role IDs the user account is expected to have.
   * @param array|null $field_integer
   *   The value of the integer field.
   * @param int|false $field_file_target_id
   *   (optional) The target ID of the file field.
   * @param bool $has_picture
   *   (optional) Whether the user is expected to have a picture attached.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $label, string $mail, string $password, int $created, int $access, int $login, bool $blocked, string $entity_langcode, string $prefered_langcode, string $timezone, string $init, array $roles, ?array $field_integer, $field_file_target_id = FALSE, bool $has_picture = FALSE): void {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($id);
    $this->assertInstanceOf(UserInterface::class, $user);
    $this->assertSame($label, $user->label());
    $this->assertSame($mail, $user->getEmail());
    $this->assertSame($password, $user->getPassword());
    $this->assertSame($created, (int) $user->getCreatedTime());
    $this->assertSame($access, (int) $user->getLastAccessedTime());
    $this->assertSame($login, (int) $user->getLastLoginTime());
    $this->assertNotSame($blocked, (bool) $user->isBlocked());

    // Ensure the user's langcode, preferred_langcode and
    // preferred_admin_langcode are valid.
    // $user->getPreferredLangcode() might fallback to default language if the
    // user preferred language is not configured on the site. We just want to
    // test if the value was imported correctly.
    $language_manager = $this->container->get('language_manager');
    $default_langcode = $language_manager->getDefaultLanguage()->getId();
    if ($prefered_langcode == '') {
      $this->assertSame('en', $user->langcode->value);
      $this->assertSame($default_langcode, $user->preferred_langcode->value);
      $this->assertSame($default_langcode, $user->preferred_admin_langcode->value);
    }
    elseif ($language_manager->getLanguage($prefered_langcode) === NULL) {
      $this->assertSame($default_langcode, $user->langcode->value);
      $this->assertSame($default_langcode, $user->preferred_langcode->value);
      $this->assertSame($default_langcode, $user->preferred_admin_langcode->value);
    }
    else {
      $this->assertSame($entity_langcode, $user->langcode->value);
      $this->assertSame($prefered_langcode, $user->preferred_langcode->value);
      $this->assertSame($prefered_langcode, $user->preferred_admin_langcode->value);
    }

    $this->assertSame($timezone, $user->getTimeZone());
    $this->assertSame($init, $user->getInitialEmail());
    $this->assertSame($roles, $user->getRoles());
    $this->assertSame($has_picture, !$user->user_picture->isEmpty());
    if (!is_null($field_integer)) {
      $this->assertTrue($user->hasField('field_integer'));
      $this->assertEquals($field_integer[0], $user->field_integer->value);
    }
    if (!empty($field_file_target_id)) {
      $this->assertTrue($user->hasField('field_file'));
      $this->assertSame($field_file_target_id, $user->field_file->target_id);
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
        ->fields('ur', ['rid'])
        ->condition('ur.uid', $source->uid)
        ->execute()
        ->fetchCol();
      $roles = [RoleInterface::AUTHENTICATED_ID];
      $id_map = $this->getMigration('d7_user_role')->getIdMap();
      foreach ($rids as $rid) {
        $role = $id_map->lookupDestinationIds([$rid])[0];
        $roles[] = reset($role);
      }

      $entity_translation = Database::getConnection('default', 'migrate')
        ->select('entity_translation', 'et')
        ->fields('et', ['language'])
        ->condition('et.entity_type', 'user')
        ->condition('et.entity_id', $source->uid)
        ->condition('et.source', '')
        ->execute()
        ->fetchField();
      $entity_language = $entity_translation ?: $source->language;

      $field_integer = Database::getConnection('default', 'migrate')
        ->select('field_data_field_integer', 'fi')
        ->fields('fi', ['field_integer_value'])
        ->condition('fi.entity_id', $source->uid)
        ->condition('fi.language', $entity_language)
        ->execute()
        ->fetchCol();
      $field_integer = !empty($field_integer) ? $field_integer : NULL;

      $field_file = Database::getConnection('default', 'migrate')
        ->select('field_data_field_file', 'ff')
        ->fields('ff', ['field_file_fid'])
        ->condition('ff.entity_id', $source->uid)
        ->execute()
        ->fetchField();

      $this->assertEntity(
        $source->uid,
        $source->name,
        $source->mail,
        $source->pass,
        $source->created,
        $source->access,
        $source->login,
        $source->status,
        $entity_language,
        $source->language,
        $source->timezone,
        $source->init,
        $roles,
        $field_integer,
        $field_file
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

    // Tests the Drupal 7 user entity translations to Drupal 8 migration.
    $manager = $this->container->get('content_translation.manager');

    // Get the user and its translations.
    $user = User::load(2);
    $user_fr = $user->getTranslation('fr');
    $user_is = $user->getTranslation('is');

    // Test that fields translated with Entity Translation are migrated.
    $this->assertSame('99', $user->field_integer->value);
    $this->assertSame('9', $user_fr->field_integer->value);
    $this->assertSame('1', $user_is->field_integer->value);

    // Test that the French translation metadata is correctly migrated.
    $metadata_fr = $manager->getTranslationMetadata($user_fr);
    $this->assertSame('en', $metadata_fr->getSource());
    $this->assertSame('1', $metadata_fr->getAuthor()->uid->value);
    $this->assertSame('1531663916', $metadata_fr->getCreatedTime());
    $this->assertFalse($metadata_fr->isOutdated());
    $this->assertFalse($metadata_fr->isPublished());

    // Test that the Icelandic translation metadata is correctly migrated.
    $metadata_is = $manager->getTranslationMetadata($user_is);
    $this->assertSame('en', $metadata_is->getSource());
    $this->assertSame('2', $metadata_is->getAuthor()->uid->value);
    $this->assertSame('1531663925', $metadata_is->getCreatedTime());
    $this->assertTrue($metadata_is->isOutdated());
    $this->assertTrue($metadata_is->isPublished());

    // Test that untranslatable properties are the same as the source language.
    $this->assertSame($user->label(), $user_fr->label());
    $this->assertSame($user->label(), $user_is->label());
    $this->assertSame($user->getEmail(), $user_fr->getEmail());
    $this->assertSame($user->getEmail(), $user_is->getEmail());
    $this->assertSame($user->getPassword(), $user_fr->getPassword());
    $this->assertSame($user->getPassword(), $user_is->getPassword());
  }

}
