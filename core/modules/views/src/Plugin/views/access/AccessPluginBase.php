<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\access\AccessPluginBase.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\PluginBase;
use Symfony\Component\Routing\Route;

/**
 * @defgroup views_access_plugins Views access plugins
 * @{
 * Plugins to handle access checking for views.
 *
 * Access plugins are responsible for controlling access to the view.
 *
 * Access plugins extend \Drupal\views\Plugin\views\access\AccessPluginBase,
 * implementing the access() and alterRouteDefinition() methods. They must be
 * annotated with \Drupal\views\Annotation\ViewsAccess annotation, and they
 * must be in namespace directory Plugin\views\access.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * The base plugin to handle access control.
 *
 * Access plugins are responsible for controlling a user's access to the view.
 * Views includes plugins for checking user roles and individual permissions.
 *
 * To define an access control plugin, extend this base class. Your access
 * plugin should have an annotation that includes the plugin's metadata, for
 * example:
 * @Plugin(
 *   id = "denyall",
 *   title = @Translation("No Access"),
 *   help = @Translation("Will not be accessible.")
 * )
 * The definition should include the following keys:
 * - id: The unique identifier of your access plugin.
 * - title: The human-readable name for your access plugin.
 * - help: A short help message for your plugin.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
abstract class AccessPluginBase extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Unknown');
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
