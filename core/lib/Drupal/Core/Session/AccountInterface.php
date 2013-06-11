<?php

/**
 * @file
 * Contains \Drupal\Core\Session\AccountInterface.
 */

namespace Drupal\Core\Session;

/**
 * Defines an account interface which represents the current user.
 *
 * Defines an object that has a user id, roles and can have session data. The
 * interface is implemented both by the global session and the user entity.
 */
interface AccountInterface {

  /**
   * Returns the user ID or 0 for anonymous.
   *
   * @return int
   *   The user ID.
   */
  public function id();

  /**
   * Returns a list of roles.
   *
   * @return array
   *   List of role IDs.
   */
  public function getRoles();

  /**
   * Returns the session ID.
   *
   * @return string|NULL
   *   The session ID or NULL if this user does not have an active session.
   */
  public function getSessionId();

  /**
   * Returns the secure session ID.
   *
   * @return string|NULL
   *   The session ID or NULL if this user does not have an active secure session.
   */
  public function getSecureSessionId();

  /**
   * Returns the session data.
   *
   * @return array
   *   Array with the session data that belongs to this object.
   */
  public function getSessionData();

}
