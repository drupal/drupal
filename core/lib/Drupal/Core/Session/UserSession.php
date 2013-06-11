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
  public $uid;

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

}
