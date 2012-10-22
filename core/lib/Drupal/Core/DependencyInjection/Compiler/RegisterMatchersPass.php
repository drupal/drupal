<?php

/**
 * @file
 * Contains Drupal\Core\DependencyInjection\Compiler\RegisterMatchersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'chained_matcher' to the 'matcher' service.
 */
class RegisterMatchersPass implements CompilerPassInterface {

  /**
   * Adds services tagged 'chained_matcher' to the 'matcher' service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *  The container to process.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('matcher')) {
      return;
    }
    $matcher = $container->getDefinition('matcher');
    foreach ($container->findTaggedServiceIds('chained_matcher') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $matcher->addMethodCall('add', array(new Reference($id), $priority));
    }
  }
}
