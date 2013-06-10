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
class UserStorageController extends DatabaseStorageControllerNG {

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
    $result = db_query('SELECT rid, uid FROM {users_roles} WHERE uid IN (:uids)', array(':uids' => array_keys($queried_users)));
    foreach ($result as $record) {
      $queried_users[$record->uid]->roles[] = $record->rid;
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_user_load().
    parent::attachLoad($queried_users, $load_revision);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    if (!isset($values['created'])) {
      $values['created'] = REQUEST_TIME;
    }
    // Users always have the authenticated user role.
    $values['roles'][] = DRUPAL_AUTHENTICATED_RID;

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
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $entity) {
    // Update the user password if it has changed.
    if ($entity->isNew() || ($entity->pass->value && $entity->pass->value != $entity->original->pass->value)) {
      // Allow alternate password hashing schemes.
      $entity->pass->value = $this->password->hash(trim($entity->pass->value));
      // Abort if the hashing failed and returned FALSE.
      if (!$entity->pass->value) {
        throw new EntityMalformedException('The entity does not have a password.');
      }
    }

    if (!$entity->isNew()) {
      // If the password is empty, that means it was not changed, so use the
      // original password.
      if (empty($entity->pass->value)) {
        $entity->pass->value = $entity->original->pass->value;
      }
    }

    // Store account cancellation information.
    foreach (array('user_cancel_method', 'user_cancel_notify') as $key) {
      if (isset($entity->{$key})) {
        $this->userData->set('user', $entity->id(), substr($key, 5), $entity->{$key});
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {

    if ($update) {
      // If the password has been changed, delete all open sessions for the
      // user and recreate the current one.
      if ($entity->pass->value != $entity->original->pass->value) {
        drupal_session_destroy_uid($entity->id());
        if ($entity->id() == $GLOBALS['user']->uid) {
          drupal_session_regenerate();
        }
      }

      // Update user roles if changed.
      if ($entity->roles->getValue() != $entity->original->roles->getValue()) {
        db_delete('users_roles')
          ->condition('uid', $entity->id())
          ->execute();

        $query = $this->database->insert('users_roles')->fields(array('uid', 'rid'));
        foreach ($entity->roles as $role) {
          if (!in_array($role->value, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {

            $query->values(array(
              'uid' => $entity->id(),
              'rid' => $role->value,
            ));
          }
        }
        $query->execute();
      }

      // If the user was blocked, delete the user's sessions to force a logout.
      if ($entity->original->status->value != $entity->status->value && $entity->status->value == 0) {
        drupal_session_destroy_uid($entity->id());
      }

      // Send emails after we have the new user object.
      if ($entity->status->value != $entity->original->status->value) {
        // The user's status is changing; conditionally send notification email.
        $op = $entity->status->value == 1 ? 'status_activated' : 'status_blocked';
        _user_mail_notify($op, $entity->getBCEntity());
      }
    }
    else {
      // Save user roles.
      if (count($entity->roles) > 1) {
        $query = $this->database->insert('users_roles')->fields(array('uid', 'rid'));
        foreach ($entity->roles as $role) {
          if (!in_array($role->value, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
            $query->values(array(
              'uid' => $entity->id(),
              'rid' => $role->value,
            ));
          }
        }
        $query->execute();
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    $this->database->delete('users_roles')
      ->condition('uid', array_keys($entities), 'IN')
      ->execute();
    $this->userData->delete(NULL, array_keys($entities));
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
      'type' => 'string_field',
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
    );
    $properties['login'] = array(
      'label' => t('Last login'),
      'description' => t('The time that the user last logged in.'),
      'type' => 'integer_field',
    );
    $properties['init'] = array(
      'label' => t('Init'),
      'description' => t('The email address used for initial account creation.'),
      'type' => 'string_field',
    );
    $properties['roles'] = array(
      'label' => t('Roles'),
      'description' => t('The roles the user has.'),
      'type' => 'string_field',
    );
    return $properties;
  }
}
