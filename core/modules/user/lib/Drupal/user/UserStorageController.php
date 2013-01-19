<?php

/**
 * @file
 * Definition of Drupal\user\UserStorageController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\DatabaseStorageController;

/**
 * Controller class for users.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for user objects.
 */
class UserStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  function attachLoad(&$queried_users, $load_revision = FALSE) {
    foreach ($queried_users as $key => $record) {
      $queried_users[$key]->roles = array();
      if ($record->uid) {
        $queried_users[$record->uid]->roles[DRUPAL_AUTHENTICATED_RID] = DRUPAL_AUTHENTICATED_RID;
      }
      else {
        $queried_users[$record->uid]->roles[DRUPAL_ANONYMOUS_RID] = DRUPAL_ANONYMOUS_RID;
      }
    }

    // Add any additional roles from the database.
    $result = db_query('SELECT rid, uid FROM {users_roles} WHERE uid IN (:uids)', array(':uids' => array_keys($queried_users)));
    foreach ($result as $record) {
      $queried_users[$record->uid]->roles[$record->rid] = $record->rid;
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
    $values['roles'][DRUPAL_AUTHENTICATED_RID] = DRUPAL_AUTHENTICATED_RID;

    return parent::create($values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::save().
   */
  public function save(EntityInterface $entity) {
    if (empty($entity->uid)) {
      $entity->uid = db_next_id(db_query('SELECT MAX(uid) FROM {users}')->fetchField());
      $entity->enforceIsNew();
    }
    parent::save($entity);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $entity) {
    // Update the user password if it has changed.
    if ($entity->isNew() || (!empty($entity->pass) && $entity->pass != $entity->original->pass)) {
      // Allow alternate password hashing schemes.
      $entity->pass = drupal_container()->get('password')->hash(trim($entity->pass));
      // Abort if the hashing failed and returned FALSE.
      if (!$entity->pass) {
        throw new EntityMalformedException('The entity does not have a password.');
      }
    }

    if (!$entity->isNew()) {
      // If the password is empty, that means it was not changed, so use the
      // original password.
      if (empty($entity->pass)) {
        $entity->pass = $entity->original->pass;
      }
    }

    // Prepare user roles.
    if (isset($entity->roles)) {
      $entity->roles = array_filter($entity->roles);
    }

    // Store account cancellation information.
    foreach (array('user_cancel_method', 'user_cancel_notify') as $key) {
      if (isset($entity->{$key})) {
        drupal_container()->get('user.data')->set('user', $entity->id(), substr($key, 5), $entity->{$key});
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
      if ($entity->pass != $entity->original->pass) {
        drupal_session_destroy_uid($entity->uid);
        if ($entity->uid == $GLOBALS['user']->uid) {
          drupal_session_regenerate();
        }
      }

      // Remove roles that are no longer enabled for the user.
      $entity->roles = array_filter($entity->roles);

      // Reload user roles if provided.
      if ($entity->roles != $entity->original->roles) {
        db_delete('users_roles')
          ->condition('uid', $entity->uid)
          ->execute();

        $query = db_insert('users_roles')->fields(array('uid', 'rid'));
        foreach (array_keys($entity->roles) as $rid) {
          if (!in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
            $query->values(array(
              'uid' => $entity->uid,
              'rid' => $rid,
            ));
          }
        }
        $query->execute();
      }

      // If the user was blocked, delete the user's sessions to force a logout.
      if ($entity->original->status != $entity->status && $entity->status == 0) {
        drupal_session_destroy_uid($entity->uid);
      }

      // Send emails after we have the new user object.
      if ($entity->status != $entity->original->status) {
        // The user's status is changing; conditionally send notification email.
        $op = $entity->status == 1 ? 'status_activated' : 'status_blocked';
        _user_mail_notify($op, $entity);
      }
    }
    else {
      // Save user roles.
      if (count($entity->roles) > 1) {
        $query = db_insert('users_roles')->fields(array('uid', 'rid'));
        foreach (array_keys($entity->roles) as $rid) {
          if (!in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
            $query->values(array(
              'uid' => $entity->uid,
              'rid' => $rid,
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
    db_delete('users_roles')
      ->condition('uid', array_keys($entities), 'IN')
      ->execute();
    drupal_container()->get('user.data')->delete(NULL, array_keys($entities));
  }
}
