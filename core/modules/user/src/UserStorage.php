<?php

/**
 * @file
 * Contains \Drupal\user\UserStorage.
 */

namespace Drupal\user;

use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for users.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for user objects.
 */
class UserStorage extends SqlContentEntityStorage implements UserStorageInterface {

  /**
   * Provides the password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Constructs a new UserStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, PasswordInterface $password, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);

    $this->password = $password;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('password'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    // The anonymous user account is saved with the fixed user ID of 0.
    // Therefore we need to check for NULL explicitly.
    if ($entity->id() === NULL) {
      $entity->uid->value = $this->database->nextId($this->database->query('SELECT MAX(uid) FROM {users}')->fetchField());
      $entity->enforceIsNew();
    }
    return parent::doSaveFieldItems($entity, $names);
  }

  /**
   * {@inheritdoc}
   */
  protected function isColumnSerial($table_name, $schema_name) {
    // User storage does not use a serial column for the user id.
    return $table_name == $this->revisionTable && $schema_name == $this->revisionKey;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastLoginTimestamp(UserInterface $account) {
    $this->database->update('users_field_data')
      ->fields(array('login' => $account->getLastLoginTime()))
      ->condition('uid', $account->id())
      ->execute();
    // Ensure that the entity cache is cleared.
    $this->resetCache(array($account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastAccessTimestamp(AccountInterface $account, $timestamp) {
    $this->database->update('users_field_data')
      ->fields(array(
        'access' => $timestamp,
      ))
      ->condition('uid', $account->id())
      ->execute();
    // Ensure that the entity cache is cleared.
    $this->resetCache(array($account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRoleReferences(array $rids) {
    // Remove the role from all users.
    $this->database->delete('user__roles')
        ->condition('roles_target_id', $rids)
        ->execute();

    $this->resetCache();
  }

}
