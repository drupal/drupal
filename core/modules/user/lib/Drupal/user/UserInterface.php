<?php

/**
 * @file
 * Contains \Drupal\user\UserInterface.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a user entity.
 */
interface UserInterface extends EntityInterface, AccountInterface {

  /**
   * Returns a list of roles.
   *
   * @return array
   *   List of role IDs.
   */
  public function getRoles();

  /**
   * Whether a user has a certain role.
   *
   * @param string $rid
   *   The role ID to check.
   *
   * @return bool
   *   Returns TRUE if the user has the role, otherwise FALSE.
   */
  public function hasRole($rid);

  /**
   * Add a role to a user.
   *
   * @param string $rid
   *   The role ID to add.
   */
  public function addRole($rid);

  /**
   * Remove a role from a user.
   *
   * @param string $rid
   *   The role ID to remove.
   */
  public function removeRole($rid);

  /**
   * Returns the hashed password.
   *
   * @return string
   *   The hashed password.
   */
  public function getPassword();

  /**
   * Sets the user password.
   *
   * @param string $password
   *   The new unhashed password.
   */
  public function setPassword($password);

  /**
   * Returns the e-mail address of the user.
   *
   * @return string
   *   The e-mail address.
   */
  public function getEmail();

  /**
   * Sets the e-mail address of the user.
   *
   * @param string $mail
   *   The new e-mail address of the user.
   */
  public function setEmail($mail);

  /**
   * Returns the default theme of the user.
   *
   * @return string
   *   Name of the theme.
   */
  public function getDefaultTheme();

  /**
   * Returns the user signature.
   *
   * @todo: Convert this to a configurable field.
   *
   * @return string
   *   The signature text.
   */
  public function getSignature();

  /**
   * Returns the signature format.
   *
   * @return string
   *   Name of the filter format.
   */
  public function getSignatureFormat();

  /**
   * Returns the creation time of the user as a UNIX timestamp.
   *
   * @return int
   *   Timestamp of the creation date.
   */
  public function getCreatedTime();

  /**
   * The timestamp when the user last accessed the site.
   *
   * A value of 0 means the user has never accessed the site.
   *
   * @return int
   *   Timestamp of the last access.
   */
  public function getLastAccessedTime();

  /**
   * Sets the UNIX timestamp when the user last accessed the site..
   *
   * @param int $timestamp
   *   Timestamp of the last access.
   */
  public function setLastAccessTime($timestamp);

  /**
   * Returns the UNIX timestamp when the user last logged in.
   *
   * @return int
   *   Timestamp of the last login time.
   */
  public function getLastLoginTime();

  /**
   * Sets the UNIX timestamp when the user last logged in.
   *
   * @param int $timestamp
   *   Timestamp of the last login time.
   */
  public function setLastLoginTime($timestamp);

  /**
   * Returns TRUE if the user is active.
   *
   * @return bool
   *   TRUE if the user is active, false otherwise.
   */
  public function isActive();

  /**
   * Returns TRUE if the user is blocked.
   *
   * @return bool
   *   TRUE if the user is blocked, false otherwise.
   */
  public function isBlocked();

  /**
   * Activates the user.
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
   */
  public function activate();

  /**
   * Blocks the user.
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
   */
  public function block();

  /**
   * Returns the timezone of the user.
   *
   * @return string
   *   Name of the timezone.
   */
  public function getTimeZone();

  /**
   * Returns the e-mail that was used when the user was registered.
   *
   * @return string
   *   Initial e-mail address of the user.
   */
  public function getInitialEmail();

}
