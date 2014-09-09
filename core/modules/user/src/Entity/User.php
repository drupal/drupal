<?php

/**
 * @file
 * Contains \Drupal\user\Entity\User.
 */

namespace Drupal\user\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the user entity class.
 *
 * The base table name here is plural, despite Drupal table naming standards,
 * because "user" is a reserved word in many databases.
 *
 * @ContentEntityType(
 *   id = "user",
 *   label = @Translation("User"),
 *   handlers = {
 *     "storage" = "Drupal\user\UserStorage",
 *     "storage_schema" = "Drupal\user\UserStorageSchema",
 *     "access" = "Drupal\user\UserAccessControlHandler",
 *     "list_builder" = "Drupal\user\UserListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\user\UserViewsData",
 *     "form" = {
 *       "default" = "Drupal\user\ProfileForm",
 *       "cancel" = "Drupal\user\Form\UserCancelForm",
 *       "register" = "Drupal\user\RegisterForm"
 *     },
 *     "translation" = "Drupal\user\ProfileTranslationHandler"
 *   },
 *   admin_permission = "administer user",
 *   base_table = "users",
 *   data_table = "users_field_data",
 *   label_callback = "user_format_name",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "uid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "entity.user.canonical",
 *     "edit-form" = "entity.user.edit_form",
 *     "cancel-form" = "entity.user.cancel_form",
 *   },
 *   field_ui_base_route = "entity.user.admin_form",
 * )
 */
class User extends ContentEntityBase implements UserInterface {

  /**
   * The hostname for this user.
   *
   * @var string
   */
  protected $hostname;

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || $this->id() === NULL;
  }

  /**
   * {@inheritdoc}
   */
  static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // Users always have the authenticated user role.
    $values['roles'][] = DRUPAL_AUTHENTICATED_RID;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update) {
      $session_manager = \Drupal::service('session_manager');
      // If the password has been changed, delete all open sessions for the
      // user and recreate the current one.
      if ($this->pass->value != $this->original->pass->value) {
        $session_manager->delete($this->id());
        if ($this->id() == \Drupal::currentUser()->id()) {
          $session_manager->regenerate();
        }
      }

      // Update user roles if changed.
      if ($this->getRoles() != $this->original->getRoles()) {
        $storage->deleteUserRoles(array($this->id()));
        $storage->saveRoles($this);
      }

      // If the user was blocked, delete the user's sessions to force a logout.
      if ($this->original->status->value != $this->status->value && $this->status->value == 0) {
        $session_manager->delete($this->id());
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
        $storage->saveRoles($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $uids = array_keys($entities);
    \Drupal::service('user.data')->delete(NULL, $uids);
    $storage->deleteUserRoles($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    $roles = array();

    foreach ($this->get('roles') as $role) {
      if (!($exclude_locked_roles && in_array($role->value, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID)))) {
        $roles[] = $role->value;
      }
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
  public function getHostname() {
    if (!isset($this->hostname) && \Drupal::hasRequest()) {
      $this->hostname = \Drupal::request()->getClientIp();
    }

    return $this->hostname;
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

    return $this->getRoleStorage()->isPermissionInRoles($permission, $this->getRoles());
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
  function getPreferredLangcode($fallback_to_default = TRUE) {
    $language_list = language_list();
    $preferred_langcode = $this->get('preferred_langcode')->value;
    if (!empty($preferred_langcode) && isset($language_list[$preferred_langcode])) {
      return $language_list[$preferred_langcode]->id;
    }
    else {
      return $fallback_to_default ? language_default()->id : '';
    }
  }

  /**
   * {@inheritdoc}
   */
  function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    $language_list = language_list();
    $preferred_langcode = $this->get('preferred_admin_langcode')->value;
    if (!empty($preferred_langcode) && isset($language_list[$preferred_langcode])) {
      return $language_list[$preferred_langcode]->id;
    }
    else {
      return $fallback_to_default ? language_default()->id : '';
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
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The user UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The user language code.'));

    $fields['preferred_langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Preferred admin language code'))
      ->setDescription(t("The user's preferred language code for receiving emails and viewing the site."));

    $fields['preferred_admin_langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Preferred language code'))
      ->setDescription(t("The user's preferred language code for viewing administration pages."))
      ->setDefaultValue('');

    // The name should not vary per language. The username is the visual
    // identifier for a user and needs to be consistent in all languages.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of this user.'))
      ->setDefaultValue('')
      ->setConstraints(array(
        // No Length constraint here because the UserName constraint also covers
        // that.
        'UserName' => array(),
        'UserNameUnique' => array(),
      ));

    $fields['pass'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Password'))
      ->setDescription(t('The password of this user (hashed).'));

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of this user.'))
      ->setDefaultValue('')
      ->setConstraints(array('UserMailUnique' => array()));

    // @todo Convert to a text field in https://drupal.org/node/1548204.
    $fields['signature'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Signature'))
      ->setDescription(t('The signature of this user.'))
      ->setTranslatable(TRUE);
    $fields['signature_format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Signature format'))
      ->setDescription(t('The signature format of this user.'));

    $fields['timezone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Timezone'))
      ->setDescription(t('The timezone of this user.'))
      ->setSetting('max_length', 32);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('User status'))
      ->setDescription(t('Whether the user is active or blocked.'))
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the user was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the user was last edited.'));

    $fields['access'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last access'))
      ->setDescription(t('The time that the user last accessed the site.'))
      ->setDefaultValue(0);

    $fields['login'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last login'))
      ->setDescription(t('The time that the user last logged in.'))
      ->setDefaultValue(0);

    $fields['init'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Initial email'))
      ->setDescription(t('The email address used for initial account creation.'))
      ->setDefaultValue('');

    // @todo Convert this to entity_reference_field, see
    // https://drupal.org/node/2044859.
    $fields['roles'] = BaseFieldDefinition::create('string')
      ->setCustomStorage(TRUE)
      ->setLabel(t('Roles'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('The roles the user has.'));

    return $fields;
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\user\RoleStorageInterface
   *   The role storage object.
   */
  protected function getRoleStorage() {
    return \Drupal::entityManager()->getStorage('user_role');
  }

}
