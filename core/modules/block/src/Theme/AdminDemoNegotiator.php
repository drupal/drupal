<?php

/**
 * @file
 * Contains \Drupal\block\Theme\AdminDemoNegotiator.
 */

namespace Drupal\block\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Negotiates the theme for the block admin demo page via the URL.
 */
class AdminDemoNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->attributes->get(RouteObjectInterface::ROUTE_NAME) == 'block.admin_demo';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    // We return exactly what was passed in, to guarantee that the page will
    // always be displayed using the theme whose blocks are being configured.
    return $request->attributes->get('theme');
  }

}
