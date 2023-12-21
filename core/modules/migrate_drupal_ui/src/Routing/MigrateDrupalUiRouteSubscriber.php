<?php

namespace Drupal\migrate_drupal_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the controller for Migrate Message route.
 */
class MigrateDrupalUiRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('migrate.messages');
    if ($route) {
      $route->setDefault('_controller', '\Drupal\migrate_drupal_ui\Controller\MigrateMessageController::overview');
    }
  }

}
