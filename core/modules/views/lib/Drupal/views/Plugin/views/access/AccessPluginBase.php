<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\AccessPluginBase.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;
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
   * Retrieve the options when this is a new access
   * control plugin
   */
  protected function defineOptions() { return array(); }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for validating options
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  public function submitOptionsForm(&$form, &$form_state) { }

  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  public function summaryTitle() {
    return t('Unknown');
  }

  /**
   * Determine if the current user has access or not.
   *
   * @param Drupal\user\User $account
   *   The user who wants to access this view.
   *
   * @return TRUE
   *   Returns whether the user has access to the view.
   */
  abstract public function access($account);

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
