<?php

/**
 * @file
 * Contains \Drupal\node\NodeTypeInterface.
 */

namespace Drupal\node;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a node type entity.
 */
interface NodeTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the configured node type settings of a given module, if any.
   *
   * @param string $module
   *   The name of the module whose settings to return.
   *
   * @return array
   *   An associative array containing the module's settings for the node type.
   *   Note that this can be empty, and default values do not necessarily exist.
   */
  public function getModuleSettings($module);

  /**
   * Determines whether the node type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

}
