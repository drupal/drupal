<?php

declare(strict_types=1);

namespace Drupal\user_disable_password_reset;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Disables access to the password reset page.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    $route = $collection->get('user.pass');
    $route->setRequirement('_access', 'FALSE');
  }

}
