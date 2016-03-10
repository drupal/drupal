<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeNegotiatorInterface.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for classes which determine the active theme.
 *
 * To set the active theme, create a new service tagged with 'theme_negotiator'
 * (see the theme.negotiator.admin_theme service in user.services.yml for an
 * example). Your service class needs to implement this interface.
 *
 * If you are setting a theme which is closely tied to the functionality of a
 * particular page or set of pages (such that the page might not function
 * correctly if a different theme is used), make sure to set the priority on
 * the service to a high number so that it is not accidentally overridden by
 * other theme negotiators. By convention, a priority of "1000" is used in
 * these cases; see \Drupal\Core\Theme\AjaxBasePageNegotiator and
 * core.services.yml for an example.
 */
interface ThemeNegotiatorInterface {

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match);

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   The name of the theme, or NULL if other negotiators, like the configured
   *   default one, should be used instead.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match);

}
