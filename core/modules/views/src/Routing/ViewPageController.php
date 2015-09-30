<?php

/**
 * @file
 * Contains \Drupal\views\Routing\ViewPageController.
 */

namespace Drupal\views\Routing;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\display\Page;

/**
 * Defines a page controller to execute and render a view.
 */
class ViewPageController {

  /**
   * Handler a response for a given view and display.
   *
   * @param string $view_id
   *   The ID of the view
   * @param string $display_id
   *   The ID of the display.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @return null|void
   */
  public function handle($view_id, $display_id, RouteMatchInterface $route_match) {
    $args = array();
    $route = $route_match->getRouteObject();
    $map = $route->hasOption('_view_argument_map') ? $route->getOption('_view_argument_map') : array();

    foreach ($map as $attribute => $parameter_name) {
      // Allow parameters be pulled from the request.
      // The map stores the actual name of the parameter in the request. Views
      // which override existing controller, use for example 'node' instead of
      // arg_nid as name.
      if (isset($map[$attribute])) {
        $attribute = $map[$attribute];
      }
      if ($arg = $route_match->getRawParameter($attribute)) {
      }
      else {
        $arg = $route_match->getParameter($attribute);
      }

      if (isset($arg)) {
        $args[] = $arg;
      }
    }

    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase $class */
    $class = $route->getOption('_view_display_plugin_class');
    if ($route->getOption('returns_response')) {
      /** @var \Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface $class */
      return $class::buildResponse($view_id, $display_id, $args);
    }
    else {
      $build = $class::buildBasicRenderable($view_id, $display_id, $args, $route);
      Page::setPageRenderArray($build);

      views_add_contextual_links($build, 'page', $display_id, $build);

      return $build;
    }
  }

}
