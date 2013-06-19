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
use Drupal\user\UserBCDecorator;
use Drupal\user\UserInterface;
use Drupal\Core\Language\Language;

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
 *       "profile" = "Drupal\user\ProfileFormController",
 *       "register" = "Drupal\user\RegisterFormController"
 *     },
 *     "translation" = "Drupal\user\ProfileTranslationController"
 *   },
 *   default_operation = "profile",
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
   * The user ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uid;

  /**
   * The user UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The unique user name.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $name;

  /**
   * The user's password (hashed).
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $pass;

  /**
   * The user's email address.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $mail;

  /**
   * The user's default theme.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $theme;

  /**
   * The user's signature.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $signature;

  /**
   * The user's signature format.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $signature_format;

  /**
   * The timestamp when the user was created.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $created;

  /**
   * The timestamp when the user last accessed the site. A value of 0 means the
   * user has never accessed the site.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $access;

  /**
   * The timestamp when the user last logged in. A value of 0 means the user has
   * never logged in.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $login;

  /**
   * Whether the user is active (1) or blocked (0).
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $status;

  /**
   * The user's timezone.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $timezone;

  /**
   * The user's langcode.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $langcode;

  /**
   * The user's preferred langcode for receiving emails and viewing the site.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $preferred_langcode;

  /**
   * The user's preferred langcode for viewing administration pages.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $preferred_admin_langcode;

  /**
   * The email address used for initial account creation.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $init;

  /**
   * The user's roles.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $roles;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  protected function init() {
    parent::init();
    unset($this->access);
    unset($this->created);
    unset($this->init);
    unset($this->login);
    unset($this->mail);
    unset($this->name);
    unset($this->pass);
    unset($this->preferred_admin_langcode);
    unset($this->preferred_langcode);
    unset($this->roles);
    unset($this->signature);
    unset($this->signature_format);
    unset($this->status);
    unset($this->theme);
    unset($this->timezone);
    unset($this->uid);
    unset($this->uuid);
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
        if ($this->id() == $GLOBALS['user']->uid) {
          drupal_session_regenerate();
        }
      }

      // Update user roles if changed.
      if ($this->roles->getValue() != $this->original->roles->getValue()) {
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
        _user_mail_notify($op, $this->getBCEntity());
      }
    }
    else {
      // Save user roles.
      if (count($this->roles) > 1) {
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
  public function getBCEntity() {
    if (!isset($this->bcEntity)) {
      // Initialize field definitions so that we can pass them by reference.
      $this->getPropertyDefinitions();
      $this->bcEntity = new UserBCDecorator($this, $this->fieldDefinitions);
    }
    return $this->bcEntity;
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

}
