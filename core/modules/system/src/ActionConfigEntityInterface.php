<?php

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an action entity.
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
   * The operation type can be NULL if no type is specified.
   *
   * @return string|null
   *   The operation type, or NULL if no type is specified.
   */
  public function getType();

  /**
   * Returns the operation plugin.
   *
   * @return \Drupal\Core\Action\ActionInterface
   */
  public function getPlugin();

}
