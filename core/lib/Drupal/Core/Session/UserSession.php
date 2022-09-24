<?php

namespace Drupal\Core\Session;

/**
 * An implementation of the user account interface for the global user.
 *
 * @todo: Change all properties to protected.
 */
#[\AllowDynamicProperties]
class UserSession implements AccountInterface {

  /**
   * User ID.
   *
   * @var int
   */
  protected $uid = 0;

  /**
   * List of the roles this user has.
   *
   * Defaults to the anonymous role.
   *
   * @var array
   */
  protected $roles = [AccountInterface::ANONYMOUS_ROLE];

  /**
   * The Unix timestamp when the user last accessed the site.
   *
   * @var string
   */
  protected $access;

  /**
   * The name of this account.
   *
   * @var string
   */
  public $name = '';

  /**
   * The preferred language code of the account.
   *
   * @var string
   */
  protected $preferred_langcode;

  /**
   * The preferred administrative language code of the account.
   *
   * @var string
   */
  protected $preferred_admin_langcode;

  /**
   * The email address of this account.
   *
   * @var string
   */
  protected $mail;

  /**
   * The timezone of this account.
   *
   * @var string
   */
  protected $timezone;

  /**
   * Constructs a new user session.
   *
   * @param array $values
   *   Array of initial values for the user session.
   */
  public function __construct(array $values = []) {
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->uid;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    $roles = $this->roles;

    if ($exclude_locked_roles) {
      $roles = array_values(array_diff($roles, [AccountInterface::ANONYMOUS_ROLE, AccountInterface::AUTHENTICATED_ROLE]));
    }

    return $roles;
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
  public function isAuthenticated() {
    return $this->uid > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->uid == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($fallback_to_default = TRUE) {
    $language_list = \Drupal::languageManager()->getLanguages();
    if (!empty($this->preferred_langcode) && isset($language_list[$this->preferred_langcode])) {
      return $language_list[$this->preferred_langcode]->getId();
    }
    else {
      return $fallback_to_default ? \Drupal::languageManager()->getDefaultLanguage()->getId() : '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    $language_list = \Drupal::languageManager()->getLanguages();
    if (!empty($this->preferred_admin_langcode) && isset($language_list[$this->preferred_admin_langcode])) {
      return $language_list[$this->preferred_admin_langcode]->getId();
    }
    else {
      return $fallback_to_default ? \Drupal::languageManager()->getDefaultLanguage()->getId() : '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName() {
    $name = $this->name ?: \Drupal::config('user.settings')->get('anonymous');
    \Drupal::moduleHandler()->alter('user_format_name', $name, $this);
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->mail;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
    return $this->timezone;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->access;
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\user\RoleStorageInterface
   *   The role storage object.
   */
  protected function getRoleStorage() {
    return \Drupal::entityTypeManager()->getStorage('user_role');
  }

}
