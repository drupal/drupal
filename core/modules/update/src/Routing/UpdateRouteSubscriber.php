<?php

declare(strict_types=1);

namespace Drupal\update\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for Update module routes.
 */
class UpdateRouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs a new UpdateRouteSubscriber.
   */
  public function __construct(
    protected Settings $settings,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->settings->get('allow_authorize_operations', TRUE)) {
      return;
    }
    $routes = [
      'update.report_update',
      'update.module_update',
      'update.theme_update',
      'update.confirmation_page',
    ];
    foreach ($routes as $route) {
      $route = $collection->get($route);
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
