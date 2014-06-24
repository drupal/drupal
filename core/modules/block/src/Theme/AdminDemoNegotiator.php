<?php

/**
 * @file
 * Contains \Drupal\block\Theme\AdminDemoNegotiator.
 */

namespace Drupal\block\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Negotiates the theme for the block admin demo page via the URL.
 */
class AdminDemoNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'block.admin_demo';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // We return exactly what was passed in, to guarantee that the page will
    // always be displayed using the theme whose blocks are being configured.
    return $route_match->getParameter('theme');
  }

}
