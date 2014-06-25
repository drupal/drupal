<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionInterface.
 */

namespace Drupal\Core\Action;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;

/**
 * Provides an interface for an Action plugin.
 *
 * @see \Drupal\Core\Annotation\Action
 * @see \Drupal\Core\Action\ActionManager
 * @see \Drupal\Core\Action\ActionBase
 * @see plugin_api
 */
interface ActionInterface extends ExecutableInterface, PluginInspectionInterface {

  /**
   * Executes the plugin for an array of objects.
   *
   * @param array $objects
   *   An array of entities.
   */
  public function executeMultiple(array $objects);

}
