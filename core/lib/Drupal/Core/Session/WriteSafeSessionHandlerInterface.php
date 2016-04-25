<?php

namespace Drupal\Core\Session;

/**
 * Provides an interface for session handlers where writing can be disabled.
 */
interface WriteSafeSessionHandlerInterface {

  /**
   * Sets whether or not a session may be written to storage.
   *
   * It is not possible to enforce writing of the session data. This method is
   * only capable of forcibly disabling that session data is written to storage.
   *
   * @param bool $flag
   *   TRUE if the session the session is allowed to be written, FALSE
   *   otherwise.
   */
  public function setSessionWritable($flag);

  /**
   * Returns whether or not a session may be written to storage.
   *
   * @return bool
   *   TRUE if the session the session is allowed to be written, FALSE
   *   otherwise.
   */
  public function isSessionWritable();

}
