<?php

/**
 * @file
 * Definition of Drupal\user\UserStorageController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityBCDecorator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Database\Connection;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Controller class for users.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for user objects.
 */
class UserStorageController extends DatabaseStorageControllerNG implements UserStorageControllerInterface {

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
   * @param string $entityType
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct($entity_type, $entity_info, Connection $database, PasswordInterface $password, UserDataInterface $user_data) {
    parent::__construct($entity_type, $entity_info, $database);

    $this->password = $password;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('database'),
      $container->get('password'),
      $container->get('user.data')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  function attachLoad(&$queried_users, $load_revision = FALSE) {
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

    // Call the default attachLoad() method. This will add fields and call
    // hook_user_load().
    parent::attachLoad($queried_users, $load_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values) {
    return parent::create($values)->getBCEntity();
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::save().
   */
  public function save(EntityInterface $entity) {
    if (!$entity->id()) {
      $entity->uid->value = $this->database->nextId($this->database->query('SELECT MAX(uid) FROM {users}')->fetchField());
      $entity->enforceIsNew();
    }
    // There are some cases that pre-set ->original for performance. Make sure
    // original is not a BC decorator.
    if ($entity->original instanceof EntityBCDecorator) {
      $entity->original = $entity->original->getNGEntity();
    }
    parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function saveRoles(EntityInterface $user) {
    $query = $this->database->insert('users_roles')->fields(array('uid', 'rid'));
    foreach ($user->roles as $role) {
      if (!in_array($role->value, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
        $query->values(array(
          'uid' => $user->id(),
          'rid' => $role->value,
        ));
      }
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function addRoles(array $users) {
    $result = db_query('SELECT rid, uid FROM {users_roles} WHERE uid IN (:uids)', array(':uids' => array_keys($users)));
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
  protected function invokeHook($hook, EntityInterface $entity) {
    $function = 'field_attach_' . $hook;
    // @todo: field_attach_delete_revision() is named the wrong way round,
    // consider renaming it.
    if ($function == 'field_attach_revision_delete') {
      $function = 'field_attach_delete_revision';
    }
    if (!empty($this->entityInfo['fieldable']) && function_exists($function)) {
      $function($entity->getBCEntity());
    }

    // Invoke the hook.
    \Drupal::moduleHandler()->invokeAll($this->entityType . '_' . $hook, array($entity->getBCEntity()));
    // Invoke the respective entity-level hook.
    \Drupal::moduleHandler()->invokeAll('entity_' . $hook, array($entity->getBCEntity(), $this->entityType));
  }

  /**
   * {@inheritdoc}
   */
  public function baseFieldDefinitions() {
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The user UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The user language code.'),
      'type' => 'language_field',
    );
    $properties['preferred_langcode'] = array(
      'label' => t('Language code'),
      'description' => t("The user's preferred langcode for receiving emails and viewing the site."),
      'type' => 'language_field',
    );
    $properties['preferred_admin_langcode'] = array(
      'label' => t('Language code'),
      'description' => t("The user's preferred langcode for viewing administration pages."),
      'type' => 'language_field',
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t('The name of this user'),
      'type' => 'string_field',
      'settings' => array('default_value' => ''),
    );
    $properties['pass'] = array(
      'label' => t('Name'),
      'description' => t('The password of this user (hashed)'),
      'type' => 'string_field',
    );
    $properties['mail'] = array(
      'label' => t('Name'),
      'description' => t('The e-mail of this user'),
      'type' => 'string_field',
      'settings' => array('default_value' => ''),
    );
    $properties['signature'] = array(
      'label' => t('Name'),
      'description' => t('The signature of this user'),
      'type' => 'string_field',
    );
    $properties['signature_format'] = array(
      'label' => t('Name'),
      'description' => t('The signature format of this user'),
      'type' => 'string_field',
    );
    $properties['theme'] = array(
      'label' => t('Theme'),
      'description' => t('The default theme of this user'),
      'type' => 'string_field',
    );
    $properties['timezone'] = array(
      'label' => t('Timeone'),
      'description' => t('The timezone of this user'),
      'type' => 'string_field',
    );
    $properties['status'] = array(
      'label' => t('User status'),
      'description' => t('Whether the user is active (1) or blocked (0).'),
      'type' => 'boolean_field',
      'settings' => array('default_value' => 1),
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the node was created.'),
      'type' => 'integer_field',
    );
    $properties['access'] = array(
      'label' => t('Last access'),
      'description' => t('The time that the user last accessed the site.'),
      'type' => 'integer_field',
      'settings' => array('default_value' => 0),
    );
    $properties['login'] = array(
      'label' => t('Last login'),
      'description' => t('The time that the user last logged in.'),
      'type' => 'integer_field',
      'settings' => array('default_value' => 0),
    );
    $properties['init'] = array(
      'label' => t('Init'),
      'description' => t('The email address used for initial account creation.'),
      'type' => 'string_field',
      'settings' => array('default_value' => ''),
    );
    $properties['roles'] = array(
      'label' => t('Roles'),
      'description' => t('The roles the user has.'),
      'type' => 'string_field',
    );
    return $properties;
  }
}
