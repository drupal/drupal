<?php

/**
 * @file
 * Definition of Drupal\user\UserStorageController.
 */

namespace Drupal\user;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Database\Connection;
use Drupal\field\FieldInfo;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\FieldableDatabaseStorageController;

/**
 * Controller class for users.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for user objects.
 */
class UserStorageController extends FieldableDatabaseStorageController implements UserStorageControllerInterface {

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
   * Constructs a new UserStorageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID Service.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, FieldInfo $field_info, UuidInterface $uuid_service, PasswordInterface $password, UserDataInterface $user_data) {
    parent::__construct($entity_type, $database, $field_info, $uuid_service);

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
      $container->get('field.info'),
      $container->get('uuid'),
      $container->get('password'),
      $container->get('user.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  function postLoad(array &$queried_users) {
    foreach ($queried_users as $key => $record) {
      $queried_users[$key]->roles = array();
      if ($record->uid) {
        $queried_users[$record->uid]->roles[] = DRUPAL_AUTHENTICATED_RID;
      }
      else {
        $queried_users[$record->uid]->roles[] = DRUPAL_ANONYMOUS_RID;
      }
    }

    // Add any additional roles from the database.
    $this->addRoles($queried_users);

    // Call the default postLoad() method. This will add fields and call
    // hook_user_load().
    parent::postLoad($queried_users);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if (!$entity->id()) {
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
    $result = $this->database->query('SELECT rid, uid FROM {users_roles} WHERE uid IN (:uids)', array(':uids' => array_keys($users)));
    foreach ($result as $record) {
      $users[$record->uid]->roles[] = $record->rid;
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
