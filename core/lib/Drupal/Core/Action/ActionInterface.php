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
 * @todo WARNING: The action API is going to receive some additions before
 * release. The following additions are likely to happen:
 *  - The way configuration is handled and configuration forms are built is
 *    likely to change in order for the plugin to be of use for Rules.
 *  - Actions are going to become context-aware in
 *    https://drupal.org/node/2011038, what will deprecated the 'type'
 *    annotation.
 *  - Instead of action implementations saving entities, support for marking
 *    required context as to be saved by the execution manager will be added as
 *    part of https://www.drupal.org/node/2347017.
 *  - Actions will receive a data processing API that allows for token
 *    replacements to happen outside of the action plugin implementations,
 *    see https://www.drupal.org/node/2347023.
 *  - Actions will have to implement access control for checking who is allowed
 *    to configure or perform the action at https://www.drupal.org/node/2172017.
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
