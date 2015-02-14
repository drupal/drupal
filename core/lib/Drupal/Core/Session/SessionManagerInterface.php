<?php

/**
 * @file
 * Contains \Drupal\Core\Session\SessionManagerInterface.
 */

namespace Drupal\Core\Session;

use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * Defines the session manager interface.
 */
interface SessionManagerInterface extends SessionStorageInterface {

  /**
   * Ends a specific user's session(s).
   *
   * @param int $uid
   *   User ID.
   */
  public function delete($uid);

  /**
   * Determines whether to save session data of the current request.
   *
   * @return bool
   *   FALSE if writing session data has been disabled. TRUE otherwise.
   *
   * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
   *   Use \Drupal\Core\Session\WriteSafeSessionHandler::isSessionWritable()
   */
  public function isEnabled();

  /**
   * Temporarily disables saving of session data.
   *
   * This function allows the caller to temporarily disable writing of
   * session data, should the request end while performing potentially
   * dangerous operations, such as manipulating the global $user object.
   *
   * @see https://drupal.org/node/218104
   *
   * @return $this
   *
   * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
   *   Use \Drupal\Core\Session\WriteSafeSessionHandler::setSessionWritable(FALSE)
   */
  public function disable();

  /**
   * Re-enables saving of session data.
   *
   * @return $this
   *
   * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
   *   Use \Drupal\Core\Session\WriteSafeSessionHandler::setSessionWritable(True)
   */
  public function enable();

  /**
   * Sets the write safe session handler.
   *
   * @todo: This should be removed once all database queries are removed from
   *   the session manager class.
   *
   * @var \Drupal\Core\Session\WriteSafeSessionHandlerInterface
   */
  public function setWriteSafeHandler(WriteSafeSessionHandlerInterface $handler);

}
