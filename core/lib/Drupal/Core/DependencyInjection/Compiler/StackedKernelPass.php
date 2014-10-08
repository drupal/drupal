<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\StackedKernelPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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

    if (!$container->hasDefinition('http_kernel')) {
      return;
    }

    $stacked_kernel = $container->getDefinition('http_kernel');

    // Return now if this is not a stacked kernel.
    if ($stacked_kernel->getClass() !== 'Stack\StackedHttpKernel') {
      return;
    }

    $middlewares = [];
    $priorities = [];

    foreach ($container->findTaggedServiceIds('http_middleware') as $id => $attributes) {
      $priorities[$id] = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $middlewares[$id] = $container->getDefinition($id);
    }

    array_multisort($priorities, SORT_ASC, $middlewares);

    $decorated_id = 'http_kernel.basic';
    $middlewares_param = [new Reference($decorated_id)];
    foreach ($middlewares as $id => $decorator) {
      // Prepend a reference to the middlewares container parameter.
      array_unshift($middlewares_param, new Reference($id));

      // Prepend the inner kernel as first constructor argument.
      $arguments = $decorator->getArguments();
      array_unshift($arguments, new Reference($decorated_id));
      $decorator->setArguments($arguments);

      $decorated_id = $id;
    }

    $arguments = [$middlewares_param[0], $middlewares_param];
    $stacked_kernel->setArguments($arguments);
  }

}
