<?php

/**
 * @file
 * Definition of Drupal\config\ConfigEntityInterface.
 */

namespace Drupal\config;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface common for all configuration entities.
 */
interface ConfigEntityInterface extends EntityInterface {

  /**
   * Returns the original ID.
   *
   * @return string|null
   *   The original ID, if any.
   */
  public function getOriginalID();

}
