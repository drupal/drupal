<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityChangedInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines an interface for entity change timestamp tracking.
 *
 * This data may be useful for more precise cache invalidation (especially
 * on the client side) and concurrent editing locking.
 */
interface EntityChangedInterface {

  /**
   * Returns the timestamp of the last entity change.
   *
   * @return int
   *   The timestamp of the last entity save operation.
   */
  public function getChangedTime();

}
