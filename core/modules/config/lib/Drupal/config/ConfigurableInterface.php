<?php

/**
 * @file
 * Definition of Drupal\config\ConfigurableInterface.
 */

namespace Drupal\config;

use Drupal\entity\StorableInterface;

/**
 * Defines the interface common for all configurable entities.
 */
interface ConfigurableInterface extends StorableInterface {

  /**
   * Returns the original ID.
   *
   * @return string|null
   *   The original ID, if any.
   */
  public function getOriginalID();

}
