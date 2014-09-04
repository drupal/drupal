<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\StackedKernelPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides a compiler pass for stacked HTTP kernels.
 *
 * @see \Stack\Builder
 */
class StackedKernelPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('http_kernel_factory')) {
      return;
    }

    $http_kernel_factory = $container->getDefinition('http_kernel_factory');
    $middleware_priorities = array();
    $middleware_arguments = array();
    foreach ($container->findTaggedServiceIds('http_middleware') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $middleware_priorities[$id] = $priority;
      $definition = $container->getDefinition($id);
      $middleware_arguments[$id] = $definition->getArguments();
      array_unshift($middleware_arguments[$id], $definition->getClass());
    }
    array_multisort($middleware_priorities, SORT_DESC, $middleware_arguments, SORT_DESC);

    foreach ($middleware_arguments as $id => $push_arguments) {
      $http_kernel_factory->addMethodCall('push', $push_arguments);
    }
  }

}
