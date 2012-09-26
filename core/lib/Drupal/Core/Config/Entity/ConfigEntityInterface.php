<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityInterface.
 */

namespace Drupal\Core\Config\Entity;

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
