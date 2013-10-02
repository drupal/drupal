<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\AccessPluginBase.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\PluginBase;
use Symfony\Component\Routing\Route;

/**
 * @defgroup views_access_plugins Views access plugins
 * @{
 * The base plugin to handle access to a view.
 *
 * Therefore it primarily has to implement the access and the alterRouteDefinition
 * method.
 */

/**
 * The base plugin to handle access control.
 */
abstract class AccessPluginBase extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return t('Unknown');
  }

  /**
   * Determine if the current user has access or not.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user who wants to access this view.
   *
   * @return TRUE
   *   Returns whether the user has access to the view.
   */
  abstract public function access(AccountInterface $account);

  /**
   * Allows access plugins to alter the route definition of a view.
   *
   * Likely the access plugin will add new requirements, so its custom access
   * checker can be applied.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to change.
   */
  abstract public function alterRouteDefinition(Route $route);

}

/**
 * @}
 */
