<?php

namespace Drupal\Core\Database\Query;

/**
 * Interface for a query that accepts placeholders.
 */
interface PlaceholderInterface {

  /**
   * Returns a unique identifier for this object.
   */
  public function uniqueIdentifier();

  /**
   * Returns the next placeholder ID for the query.
   *
   * @return
   *   The next available placeholder ID as an integer.
   */
  public function nextPlaceholder();
}
