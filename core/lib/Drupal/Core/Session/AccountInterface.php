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
   * Checks whether a user has a certain permission.
   *
   * @param string $permission
   *   The permission string to check.
   *
   * @return bool
   *   TRUE if the user has the permission, FALSE otherwise.
   */
  public function hasPermission($permission);

  /**
   * Returns the session ID.
   *
   * @return string|null
   *   The session ID or NULL if this user does not have an active session.
   */
  public function getSessionId();

  /**
   * Returns the secure session ID.
   *
   * @return string|null
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

  /**
   * Returns TRUE if the account is authenticated.
   *
   * @return bool
   *   TRUE if the account is authenticated.
   */
  public function isAuthenticated();

  /**
   * Returns TRUE if the account is anonymous.
   *
   * @return bool
   *   TRUE if the account is anonymous.
   */
  public function isAnonymous();

  /**
   * Returns the preferred language code of the account.
   *
   * @param string $default
   *   (optional) Default language code to return if the account
   *   has no valid language, defaults to the site default language.
   *
   * @return string
   *   The language code that is preferred by the account.
   */
  public function getPreferredLangcode($default = NULL);

  /**
   * Returns the preferred administrative language code of the account.
   *
   * Defines which language is used on administrative pages.
   *
   * @param string $default
   *   (optional) Default language code to return if the account
   *   has no valid language, defaults to the site default language.
   *
   * @return string
   *   The language code that is preferred by the account.
   */
  public function getPreferredAdminLangcode($default = NULL);

  /**
   * Returns the username of this account.
   *
   * By default, the passed-in object's 'name' property is used if it exists, or
   * else, the site-defined value for the 'anonymous' variable. However, a module
   * may override this by implementing
   * hook_user_format_name_alter(&$name, $account).
   *
   * @see hook_user_format_name_alter()
   *
   * @return
   *   An unsanitized string with the username to display. The code receiving
   *   this result must ensure that \Drupal\Component\Utility\String::checkPlain()
   *   is called on it before it is
   *   printed to the page.
   */
  public function getUsername();

  /**
   * Returns the e-mail address of this account.
   *
   * @return string
   *   The e-mail address.
   */
  public function getEmail();

  /**
   * Returns the timezone of this account.
   *
   * @return string
   *   Name of the timezone.
   */
  public function getTimeZone();

  /**
   * The timestamp when the account last accessed the site.
   *
   * A value of 0 means the user has never accessed the site.
   *
   * @return int
   *   Timestamp of the last access.
   */
  public function getLastAccessedTime();

}
