<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the authentication_providers container parameter.
 */
class AuthenticationProviderPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function process(ContainerBuilder $container) {
    $authentication_providers = [];
    foreach ($container->findTaggedServiceIds('authentication_provider') as $service_id => $attributes) {
      $authentication_provider = $attributes[0]['provider_id'];
      if ($provider_tag = $container->getDefinition($service_id)->getTag('_provider')) {
        $authentication_providers[$authentication_provider] = $provider_tag[0]['provider'];
      }
    }
    $container->setParameter('authentication_providers', $authentication_providers);
  }

}
