<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\IdentifiableInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for identifiable typed data.
 */
interface IdentifiableInterface {

  /**
   * Returns the identifier.
   *
   * @return string|int|null
   *   The object identifier, or NULL if the object does not yet have an identifier.
   */
  public function id();
}
