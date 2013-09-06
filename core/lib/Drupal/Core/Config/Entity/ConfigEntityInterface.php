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
   * Status implementations for configuration entities should follow these
   * general rules:
   *   - Status does not affect the loading of entities. I.e. Disabling
   *     configuration entities should only have UI/access implications.
   *   - It should only take effect when a 'status' key is explicitly declared
   *     in the entity_keys info of a configuration entitys annotation data.
   *   - Each entity implementation (entity/controller) is responsible for
   *     checking and managing the status.
   *
   * @return bool
   */
  public function status();

  /**
   * Retrieves the exportable properties of the entity.
   *
   * These are the values that get saved into config.
   *
   * @return array
   *   An array of exportable properties and their values.
   */
  public function getExportProperties();

}
