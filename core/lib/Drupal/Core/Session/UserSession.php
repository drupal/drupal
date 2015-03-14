<?php

/**
 * @file
 * Contains \Drupal\Core\Session\UserSession.
 */

namespace Drupal\Core\Session;

/**
 * An implementation of the user account interface for the global user.
 *
 * @todo: Change all properties to protected.
 */
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
  protected $roles = array(AccountInterface::ANONYMOUS_ROLE);

  /**
   * Session ID.
   *
   * @var string.
   */
  public $sid;

  /**
   * Secure session ID.
   *
   * @var string.
   */
  public $ssid;

  /**
   * Session data.
   *
   * @var array.
   */
  public $session;

  /**
   * The Unix timestamp when this session last requested a page.
   *
   * @var string.
   */
  protected $timestamp;

  /**
   * The name of this account.
   *
   * @var string
   */
  public $name;

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
   * The hostname for this user session.
   *
   * @var string
   */
  protected $hostname = '';

  /**
   * Constructs a new user session.
   *
   * @param array $values
   *   Array of initial values for the user session.
   */
  public function __construct(array $values = array()) {
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
      $roles = array_values(array_diff($roles, array(AccountInterface::ANONYMOUS_ROLE, AccountInterface::AUTHENTICATED_ROLE)));
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
  public function getSecureSessionId() {
    return $this->ssid;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionData() {
    return $this->session;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->sid;
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
  function getPreferredLangcode($fallback_to_default = TRUE) {
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
  function getPreferredAdminLangcode($fallback_to_default = TRUE) {
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
  public function getUsername() {
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
    return $this->timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->hostname;
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
