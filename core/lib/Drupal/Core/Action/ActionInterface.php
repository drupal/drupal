<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionInterface.
 */

namespace Drupal\Core\Action;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * Checks object access.
   *
   * @param mixed $object
   *   The object to execute the action on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE);

}
