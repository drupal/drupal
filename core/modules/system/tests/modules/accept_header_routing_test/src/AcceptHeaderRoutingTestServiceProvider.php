<?php

/**
 * @file
 * Contains \Drupal\accept_header_routing_test\AcceptHeaderRoutingTestServiceProvider.
 */

namespace Drupal\accept_header_routing_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Service provider for the accept_header_routing_test module.
 */
class AcceptHeaderRoutingTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Remove the basic content negotation middleware and replace it with a
    // basic header based one.
    $container->register('http_middleware.negotiation', 'Drupal\accept_header_routing_test\AcceptHeaderMiddleware')
      ->addTag('http_middleware', ['priority' => 400]);
  }

}
