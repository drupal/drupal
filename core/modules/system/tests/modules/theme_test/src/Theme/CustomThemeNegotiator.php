<?php

/**
 * @file
 * Contains \Drupal\theme_test\Theme\CustomThemeNegotiator.
 */

namespace Drupal\theme_test\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Just forces the 'test_theme' theme.
 */
class CustomThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return (($route_object = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) && $route_object instanceof Route && $route_object->hasOption('_custom_theme'));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    $route_object = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    return $route_object->getOption('_custom_theme');
  }

}
