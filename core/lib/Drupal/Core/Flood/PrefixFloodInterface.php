<?php

namespace Drupal\Core\Flood;

/**
 * Defines an interface for flood controllers that clear by identifier prefix.
 */
interface PrefixFloodInterface {

  /**
   * Makes the flood control mechanism forget an event by identifier prefix.
   *
   * @param string $name
   *   The name of an event.
   * @param string $prefix
   *   The prefix of the identifier to be cleared.
   */
  public function clearByPrefix(string $name, string $prefix): void;

}
