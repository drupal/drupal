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

  /**
   * Sets the original ID.
   *
   * @param string $id
   *   The new ID to set as original ID.
   *
   * @return void
   */
  public function setOriginalID($id);

  /**
   * Enables the configuration entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The configuration entity.
   */
  public function enable();

  /**
   * Disables the configuration entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The configuration entity.
   */
  public function disable();

  /**
   * Sets the status of the configuration entity.
   *
   * @param bool $status
   *   The status of the configuration entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The class instance that this method is called on.
   */
  public function setStatus($status);

  /**
   * Returns whether the configuration entity is enabled.
   *
   * @return bool
   */
  public function status();

}
