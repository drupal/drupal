<?php

/**
 * @file
 * Definition of \Drupal\router_test\RouterTestBundle.
 */

namespace Drupal\router_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers a dynamic route provider.
 */
class RouterTestBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('router_test.subscriber', 'Drupal\router_test\RouteTestSubscriber')->addTag('event_subscriber');
    $container->register('access_check.router_test', 'Drupal\router_test\Access\TestAccessCheck')
      ->addTag('access_check');
  }
}
