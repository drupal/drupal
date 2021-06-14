<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Routing\RouteProviderLazyBuilder;
use Symfony\Component\Routing\Route;

/**
 * A Route Provider front-end for use during the installer.
 */
class InstallerRouteProviderLazyBuilder extends RouteProviderLazyBuilder {

  /**
   * {@inheritdoc}
   */
  public function getRouteByName($name) {
    if ($name === '<none>' || $name === '<front>') {
      // During the installer template_preprocess_page() uses the routing system
      // to determine the front page. At this point building the router for this
      // is unnecessary work.
      return new Route('/');
    }
    return parent::getRouteByName($name);
  }

}
