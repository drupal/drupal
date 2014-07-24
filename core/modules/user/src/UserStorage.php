<?php

/**
 * @file
 * Definition of Drupal\user\UserStorage.
 */

namespace Drupal\user;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheBackendInterface;
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
    $this->database->update('users')
      ->fields(array('login' => $account->getLastLoginTime()))
      ->condition('uid', $account->id())
      ->execute();
    // Ensure that the entity cache is cleared.
    $this->resetCache(array($account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['users']['fields']['access']['not null'] = TRUE;
    $schema['users']['fields']['created']['not null'] = TRUE;
    $schema['users']['fields']['name']['not null'] = TRUE;

    // The "users" table does not use serial identifiers.
    $schema['users']['fields']['uid']['type'] = 'int';
    $schema['users']['indexes'] += array(
      'user__access' => array('access'),
      'user__created' => array('created'),
      'user__mail' => array('mail'),
    );
    $schema['users']['unique keys'] += array(
      'user__name' => array('name'),
    );

    $schema['users_roles'] = array(
      'description' => 'Maps users to roles.',
      'fields' => array(
        'uid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: {users}.uid for user.',
        ),
        'rid' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'description' => 'Primary Key: ID for the role.',
        ),
      ),
      'primary key' => array('uid', 'rid'),
      'indexes' => array(
        'rid' => array('rid'),
      ),
      'foreign keys' => array(
        'user' => array(
          'table' => 'users',
          'columns' => array('uid' => 'uid'),
        ),
      ),
    );

    return $schema;
  }

}
