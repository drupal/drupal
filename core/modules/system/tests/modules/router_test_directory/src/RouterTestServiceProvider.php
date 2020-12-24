<?php

namespace Drupal\router_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Registers a dynamic route provider.
 */
class RouterTestServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->register('router_test.subscriber', 'Drupal\router_test\RouteTestSubscriber')
      ->addTag('event_subscriber');
    $container->register('access_check.router_test', 'Drupal\router_test\Access\TestAccessCheck')
      ->addTag('access_check', ['applies_to' => '_access_router_test']);
    $container->register('router_test.early_exception.subscriber', 'Drupal\router_test\RouterTestEarlyExceptionSubscriber')
      ->addTag('event_subscriber');
  }

}
