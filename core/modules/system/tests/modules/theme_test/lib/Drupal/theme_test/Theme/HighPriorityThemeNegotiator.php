<?php

/**
 * @file
 * Contains \Drupal\theme_test\Theme\HighPriorityThemeNegotiator.
 */

namespace Drupal\theme_test\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements a test theme negotiator which was configured with a high priority.
 */
class HighPriorityThemeNegotiator implements ThemeNegotiatorInterface {



  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return (($route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME)) && $route_name == 'theme_test.priority');
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    return 'stark';
  }

}
