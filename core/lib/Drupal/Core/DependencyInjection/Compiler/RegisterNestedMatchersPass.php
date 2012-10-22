<?php

/**
 * @file
 * Contains Drupal\Core\DependencyInjection\Compiler\RegisterNestedMatchersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'nested_matcher' to the tagged_matcher service.
 */
class RegisterNestedMatchersPass implements CompilerPassInterface {

  /**
   * Adds services tagged 'nested_matcher' to the tagged_matcher service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('nested_matcher')) {
      return;
    }
    $nested = $container->getDefinition('nested_matcher');
    foreach ($container->findTaggedServiceIds('nested_matcher') as $id => $attributes) {
      $method = $attributes[0]['method'];
      $nested->addMethodCall($method, array(new Reference($id)));
    }
  }
}
