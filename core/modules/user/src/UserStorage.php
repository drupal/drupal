<?php

/**
 * @file
 * Definition of Drupal\user\UserStorage.
 */

namespace Drupal\user;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * Controller class for users.
 *
 * This extends the Drupal\Core\Entity\ContentEntityDatabaseStorage class,
 * adding required special handling for user objects.
 */
class UserStorage extends ContentEntityDatabaseStorage implements UserStorageInterface {

  /**
   * Provides the password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a new UserStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID Service.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, UuidInterface $uuid_service, PasswordInterface $password, UserDataInterface $user_data) {
    parent::__construct($entity_type, $database, $entity_manager, $uuid_service);

    $this->password = $password;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('uuid'),
      $container->get('password'),
      $container->get('user.data')
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
    $this->database->update('users')
      ->fields(array('login' => $account->getLastLoginTime()))
      ->condition('uid', $account->id())
      ->execute();
  }

}
