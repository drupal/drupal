<?php

/**
 * @file
 * Contains \Drupal\theme_test\Theme\HighPriorityThemeNegotiator.
 */

namespace Drupal\theme_test\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Implements a test theme negotiator which was configured with a high priority.
 */
class HighPriorityThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return ($route_match->getRouteName() == 'theme_test.priority');
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'classy';
  }

}
