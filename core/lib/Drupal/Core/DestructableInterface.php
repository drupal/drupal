<?php

namespace Drupal\Core;

/**
 * The interface for services that need explicit destruction.
 *
 * This is useful for services that need to perform additional tasks to
 * finalize operations or clean up after the response is sent and before the
 * service is terminated.
 *
 * Services using this interface need to be registered with the
 * "needs_destruction" tag.
 */
interface DestructableInterface {

  /**
   * Performs destruct operations.
   */
  public function destruct();

}
