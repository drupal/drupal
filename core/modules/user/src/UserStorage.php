<?php

/**
 * @file
 * Definition of Drupal\user\UserStorage.
 */

namespace Drupal\user;

use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Session\AccountInterface;
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
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, PasswordInterface $password) {
    parent::__construct($entity_type, $database, $entity_manager, $cache);

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
      $container->get('password')
    );
  }

  /**
   * {@inheritdoc}
   */
  function mapFromStorageRecords(array $records) {
    foreach ($records as $record) {
      $record->roles = array();
      if ($record->uid) {
        $record->roles[] = DRUPAL_AUTHENTICATED_RID;
      }
      else {
        $record->roles[] = DRUPAL_ANONYMOUS_RID;
      }
    }

    // Add any additional roles from the database.
    $this->addRoles($records);
    return parent::mapFromStorageRecords($records);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    // The anonymous user account is saved with the fixed user ID of 0.
    // Therefore we need to check for NULL explicitly.
    if ($entity->id() === NULL) {
      $entity->uid->value = $this->database->nextId($this->database->query('SELECT MAX(uid) FROM {users}')->fetchField());
      $entity->enforceIsNew();
    }
    parent::save($entity);
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
  public function saveRoles(UserInterface $account) {
    $query = $this->database->insert('users_roles')->fields(array('uid', 'rid'));
    foreach ($account->getRoles() as $rid) {
      if (!in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
        $query->values(array(
          'uid' => $account->id(),
          'rid' => $rid,
        ));
      }
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function addRoles(array $users) {
    if ($users) {
      $result = $this->database->query('SELECT rid, uid FROM {users_roles} WHERE uid IN (:uids)', array(':uids' => array_keys($users)));
      foreach ($result as $record) {
        $users[$record->uid]->roles[] = $record->rid;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUserRoles(array $uids) {
    $this->database->delete('users_roles')
      ->condition('uid', $uids)
      ->execute();
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

}
