<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\Core\Entity\User.
 */

namespace Drupal\user\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\user\UserInterface;

/**
 * Defines the user entity class.
 *
 * @EntityType(
 *   id = "user",
 *   label = @Translation("User"),
 *   module = "user",
 *   controllers = {
 *     "storage" = "Drupal\user\UserStorageController",
 *     "access" = "Drupal\user\UserAccessController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "default" = "Drupal\user\ProfileFormController",
 *       "register" = "Drupal\user\RegisterFormController"
 *     },
 *     "translation" = "Drupal\user\ProfileTranslationController"
 *   },
 *   base_table = "users",
 *   uri_callback = "user_uri",
 *   route_base_path = "admin/config/people/accounts",
 *   label_callback = "user_label",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "uid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/user/{user}",
 *     "edit-form" = "/user/{user}/edit"
 *   }
 * )
 */
class User extends EntityNG implements UserInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    if (!isset($values['created'])) {
      $values['created'] = REQUEST_TIME;
    }
    // Users always have the authenticated user role.
    $values['roles'][] = DRUPAL_AUTHENTICATED_RID;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // Update the user password if it has changed.
    if ($this->isNew() || ($this->pass->value && $this->pass->value != $this->original->pass->value)) {
      // Allow alternate password hashing schemes.
      $this->pass->value = \Drupal::service('password')->hash(trim($this->pass->value));
      // Abort if the hashing failed and returned FALSE.
      if (!$this->pass->value) {
        throw new EntityMalformedException('The entity does not have a password.');
      }
    }

    if (!$this->isNew()) {
      // If the password is empty, that means it was not changed, so use the
      // original password.
      if (empty($this->pass->value)) {
        $this->pass->value = $this->original->pass->value;
      }
    }

    // Store account cancellation information.
    foreach (array('user_cancel_method', 'user_cancel_notify') as $key) {
      if (isset($this->{$key})) {
        \Drupal::service('user.data')->set('user', $this->id(), substr($key, 5), $this->{$key});
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    if ($update) {
      // If the password has been changed, delete all open sessions for the
      // user and recreate the current one.
      if ($this->pass->value != $this->original->pass->value) {
        drupal_session_destroy_uid($this->id());
        if ($this->id() == $GLOBALS['user']->id()) {
          drupal_session_regenerate();
        }
      }

      // Update user roles if changed.
      if ($this->getRoles() != $this->original->getRoles()) {
        $storage_controller->deleteUserRoles(array($this->id()));
        $storage_controller->saveRoles($this);
      }

      // If the user was blocked, delete the user's sessions to force a logout.
      if ($this->original->status->value != $this->status->value && $this->status->value == 0) {
        drupal_session_destroy_uid($this->id());
      }

      // Send emails after we have the new user object.
      if ($this->status->value != $this->original->status->value) {
        // The user's status is changing; conditionally send notification email.
        $op = $this->status->value == 1 ? 'status_activated' : 'status_blocked';
        _user_mail_notify($op, $this);
      }
    }
    else {
      // Save user roles.
      if (count($this->getRoles()) > 1) {
        $storage_controller->saveRoles($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    $uids = array_keys($entities);
    \Drupal::service('user.data')->delete(NULL, $uids);
    $storage_controller->deleteUserRoles($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    $roles = array();
    foreach ($this->get('roles') as $role) {
      $roles[] = $role->value;
    }
    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecureSessionId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionData() {
    return array();
  }
  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRole($rid) {
    return in_array($rid, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($rid) {
    $roles = $this->getRoles();
    $roles[] = $rid;
    $this->set('roles', array_unique($roles));
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($rid) {
    $this->set('roles', array_diff($this->getRoles(), array($rid)));
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    // User #1 has all privileges.
    if ((int) $this->id() === 1) {
      return TRUE;
    }

    $roles = \Drupal::entityManager()->getStorageController('user_role')->loadMultiple($this->getRoles());

    foreach ($roles as $role) {
      if ($role->hasPermission($permission)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->get('pass')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword($password) {
    $this->get('pass')->value = $password;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->get('mail')->value = $mail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultTheme() {
    return $this->get('theme')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSignature() {
    return $this->get('signature')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSignatureFormat() {
    return $this->get('signature_format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->get('access')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastAccessTime($timestamp) {
    $this->get('access')->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastLoginTime() {
    return $this->get('login')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastLoginTime($timestamp) {
    $this->get('login')->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->get('status')->value == 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isBlocked() {
    return $this->get('status')->value == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function activate() {
    $this->get('status')->value = 1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function block() {
    $this->get('status')->value = 0;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
    return $this->get('timezone')->value;
  }

  /**
   * {@inheritdoc}
   */
  function getPreferredLangcode($default = NULL) {
    $language_list = language_list();
    $preferred_langcode = $this->get('preferred_langcode')->value;
    if (!empty($preferred_langcode) && isset($language_list[$preferred_langcode])) {
      return $language_list[$preferred_langcode]->id;
    }
    else {
      return $default ? $default : language_default()->id;
    }
  }

  /**
   * {@inheritdoc}
   */
  function getPreferredAdminLangcode($default = NULL) {
    $language_list = language_list();
    $preferred_langcode = $this->get('preferred_admin_langcode')->value;
    if (!empty($preferred_langcode) && isset($language_list[$preferred_langcode])) {
      return $language_list[$preferred_langcode]->id;
    }
    else {
      return $default ? $default : language_default()->id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialEmail() {
    return $this->get('init')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->id() > 0;
  }
  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->id() == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    $name = $this->get('name')->value ?: \Drupal::config('user.settings')->get('anonymous');
    \Drupal::moduleHandler()->alter('user_format_name', $name, $this);
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsername($username) {
    $this->set('name', $username);
    return $this;
  }

}
