<?php

declare(strict_types=1);

namespace Drupal\workspaces_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Workspaces routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Re-enable access to the workspace routes.
    $workspaces_routes = [
      'entity.workspace.collection',
      'entity.workspace.activate_form',
      'entity.workspace.publish_form',
      'entity.workspace.merge_form',
      'workspaces.switch_to_live',
    ];
    foreach ($workspaces_routes as $workspace_route) {
      if ($route = $collection->get($workspace_route)) {
        $requirements = $route->getRequirements();
        unset($requirements['_access']);
        $route->setRequirements($requirements);
      }
    }
  }

}
