<?php

declare(strict_types=1);

namespace Drupal\theme_test\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Just forces the 'test_theme' theme.
 */
class CustomThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    return ($route && $route->hasOption('_custom_theme'));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $route_match->getRouteObject()->getOption('_custom_theme');
  }

}
