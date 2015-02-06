<?php

/**
 * @file
 * Contains \Drupal\user\UserInterface.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a user entity.
 *
 * @ingroup user_api
 */
interface UserInterface extends ContentEntityInterface, EntityChangedInterface, AccountInterface {

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
   * Sets the username of this account.
   *
   * @param string $username
   *   The new user name.
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
   */
  public function setUsername($username);

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
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
   */
  public function setPassword($password);

  /**
   * Sets the email address of the user.
   *
   * @param string $mail
   *   The new email address of the user.
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
   */
  public function setEmail($mail);

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
   * Sets the user signature.
   *
   * @param string $signature
   *   The new signature text of the user.
   *
   * @return $this
   */
  public function setSignature($signature);

  /**
   * Returns the signature format.
   *
   * @return string
   *   Name of the filter format.
   */
  public function getSignatureFormat();

  /**
   * Sets the signature format.
   *
   * @param string $signature_format
   *   The name of the new filter format.
   *
   * @return $this
   */
  public function setSignatureFormat($signature_format);

  /**
   * Returns the creation time of the user as a UNIX timestamp.
   *
   * @return int
   *   Timestamp of the creation date.
   */
  public function getCreatedTime();

  /**
   * Sets the UNIX timestamp when the user last accessed the site..
   *
   * @param int $timestamp
   *   Timestamp of the last access.
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
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
   *
   * @return \Drupal\user\UserInterface
   *   The called user entity.
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
   * Returns the email that was used when the user was registered.
   *
   * @return string
   *   Initial email address of the user.
   */
  public function getInitialEmail();

}
