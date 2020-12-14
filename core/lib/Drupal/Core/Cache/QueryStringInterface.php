<?php

namespace Drupal\Core\Cache;

/**
 * Defines interface for the cache query string service.
 */
interface QueryStringInterface {

  /**
   * Changes the dummy query string added to all CSS and JavaScript files.
   *
   * Changing the dummy query string appended to CSS and JavaScript files forces
   * all browsers to reload fresh files.
   *
   * @param string|null $value
   *   Set expected query string value if provided.
   */
  public function reset(string $value = NULL): void;

  /**
   * Get query string added to all CSS and JavaScript files.
   */
  public function get(): string;

}
