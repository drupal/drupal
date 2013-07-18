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
  protected $uid;

  /**
   * Session hostname.
   *
   * @todo: This does not seem to be used, remove?
   *
   * @var string
   */
  public $hostname;

  /**
   * List of the roles this user has.
   *
   * @var array
   */
  public $roles;

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
  public $timestamp;

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
   * Constructs a new user session.
   *
   * @param array $values
   *   Array of initial values for the user sesion.
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
  public function getRoles() {
    return $this->roles;
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
  function getPreferredLangcode($default = NULL) {
    $language_list = language_list();
    if (!empty($this->preferred_langcode) && isset($language_list[$this->preferred_langcode])) {
      return $language_list[$this->preferred_langcode]->id;
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
    if (!empty($this->preferred_admin_langcode) && isset($language_list[$this->preferred_admin_langcode])) {
      return $language_list[$this->preferred_admin_langcode]->id;
    }
    else {
      return $default ? $default : language_default()->id;
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

}
