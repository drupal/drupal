<?php

declare(strict_types=1);

namespace Drupal\Core\Asset;

/**
 * Provides a cache busting query string service for asset URLs.
 */
interface AssetQueryStringInterface {

  /**
   * Resets the cache query string added to all CSS and JavaScript URLs.
   *
   * Changing the cache query string appended to CSS and JavaScript URLs forces
   * all browsers to fetch fresh files.
   */
  public function reset(): void;

  /**
   * Gets the query string value.
   */
  public function get(): string;

}
