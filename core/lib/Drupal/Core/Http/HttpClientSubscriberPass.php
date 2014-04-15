<?php

/**
 * @file
 * Contains \Drupal\Core\Http\HttpClientSubscriberPass.
 */

namespace Drupal\Core\Http;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers 'http_client_subscriber' tagged services as http client subscribers.
 */
class HttpClientSubscriberPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $http_client = $container->getDefinition('http_client');

    foreach (array_keys($container->findTaggedServiceIds('http_client_subscriber')) as $id) {
      $http_client->addMethodCall('attach', array(new Reference($id)));
    }
  }

}

