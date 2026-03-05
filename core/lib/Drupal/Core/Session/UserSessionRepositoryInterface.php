<?php

declare(strict_types=1);

namespace Drupal\Core\Session;

/**
 * Provides an interface for the user session repository.
 */
interface UserSessionRepositoryInterface {

  /**
   * Delete all session records of the given user.
   *
   * @param int $uid
   *   The user id.
   */
  public function deleteAll(int $uid): void;

}
