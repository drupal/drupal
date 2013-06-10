<?php

/**
 * @file
 * Contains \Drupal\system\ActionConfigEntityInterface.
 */

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a action entity.
 */
interface ActionConfigEntityInterface extends ConfigEntityInterface {

  /**
   * Returns whether or not this action is configurable.
   *
   * @return bool
   */
  public function isConfigurable();

  /**
   * Returns the operation type.
   *
   * @return string
   */
  public function getType();

  /**
   * Returns the operation plugin.
   *
   * @return \Drupal\Core\Action\ActionInterface
   */
  public function getPlugin();

}
