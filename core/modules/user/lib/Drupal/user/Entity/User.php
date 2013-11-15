<?php

/**
 * @file
 * Definition of Drupal\user\Entity\User.
 */

namespace Drupal\user\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\user\UserInterface;

/**
 * Defines the user entity class.
 *
 * @EntityType(
 *   id = "user",
 *   label = @Translation("User"),
 *   controllers = {
 *     "storage" = "Drupal\user\UserStorageController",
 *     "access" = "Drupal\user\UserAccessController",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\user\ProfileFormController",
 *       "cancel" = "Drupal\user\Form\UserCancelForm",
 *       "register" = "Drupal\user\RegisterFormController"
 *     },
 *     "translation" = "Drupal\user\ProfileTranslationController"
 *   },
 *   admin_permission = "administer user",
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
 *     "canonical" = "user.view",
 *     "edit-form" = "user.edit"
 *   }
 * )
 */
class User extends ContentEntityBase implements UserInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || $this->id() === NULL;
  }

  /**
   * {@inheritdoc}
   */
  static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

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
    parent::preSave($storage_controller);

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
    parent::postSave($storage_controller, $update);

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
    parent::postDelete($storage_controller, $entities);

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
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
      'property_constraints' => array(
        // No Length contraint here because the UserName constraint also covers
        // that.
        'value' => array(
          'UserName' => array(),
          'UserNameUnique' => array(),
        ),
      ),
    );
    $properties['pass'] = array(
      'label' => t('Password'),
      'description' => t('The password of this user (hashed)'),
      'type' => 'string_field',
    );
    $properties['mail'] = array(
      'label' => t('E-mail'),
      'description' => t('The e-mail of this user'),
      'type' => 'email_field',
      'settings' => array('default_value' => ''),
      'property_constraints' => array(
        'value' => array('UserMailUnique' => array()),
      ),
    );
    $properties['signature'] = array(
      'label' => t('Signature'),
      'description' => t('The signature of this user'),
      'type' => 'string_field',
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 255)),
      ),
    );
    $properties['signature_format'] = array(
      'label' => t('Signature format'),
      'description' => t('The signature format of this user'),
      // @todo Convert the type to filter_format once
      // https://drupal.org/node/1758622 is comitted
      'type' => 'string_field',
    );
    $properties['theme'] = array(
      'label' => t('Theme'),
      'description' => t('The default theme of this user'),
      'type' => 'string_field',
      'property_constraints' => array(
        'value' => array('Length' => array('max' => DRUPAL_EXTENSION_NAME_MAX_LENGTH)),
      ),
    );
    $properties['timezone'] = array(
      'label' => t('Timezone'),
      'description' => t('The timezone of this user'),
      'type' => 'string_field',
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 32)),
      ),
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
      'type' => 'email_field',
      'settings' => array('default_value' => ''),
    );
    $properties['roles'] = array(
      'label' => t('Roles'),
      'description' => t('The roles the user has.'),
      // @todo Convert this to entity_reference_field, see
      // https://drupal.org/node/2044859
      'type' => 'string_field',
    );
    return $properties;
  }

}
