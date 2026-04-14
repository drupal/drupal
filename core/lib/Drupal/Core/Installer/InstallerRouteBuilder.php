<?php

namespace Drupal\Core\Installer;

@trigger_error('\Drupal\Core\Installer\InstallerRouteBuilder is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. No replacement provided. See https://www.drupal.org/node/3324749', E_USER_DEPRECATED);

use Drupal\Core\Routing\RouteBuilder;

/**
 * Manages the router in the installer.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. No
 *   replacement provided.
 *
 * @see https://www.drupal.org/node/3324749
 */
class InstallerRouteBuilder extends RouteBuilder {

  /**
   * {@inheritdoc}
   *
   * Overridden to return no routes.
   *
   * @todo Convert installer steps into routes; add an installer.routing.yml.
   */
  protected function getRouteDefinitions() {
    return [];
  }

}
