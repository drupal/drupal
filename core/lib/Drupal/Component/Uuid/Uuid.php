<?php

namespace Drupal\Component\Uuid;

/**
 * UUID Helper methods.
 */
class Uuid {

  /**
   * The pattern used to validate a UUID string.
   */
  const VALID_PATTERN = '[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}';

  /**
   * Checks that a string appears to be in the format of a lower-case UUID.
   *
   * Implementations should not implement validation, since UUIDs should be in
   * a consistent format across all implementations.
   *
   * @param string $uuid
   *   The string to test.
   *
   * @return bool
   *   TRUE if the string is well formed, FALSE otherwise.
   */
  public static function isValid($uuid) {
    return (bool) preg_match('/^' . self::VALID_PATTERN . '$/', $uuid);
  }

}
