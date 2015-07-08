<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\ContextProvidersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds the context provider service IDs to the context manager.
 */
class ContextProvidersPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   *
   * Passes the service IDs of all context providers to the context repository.
   */
  public function process(ContainerBuilder $container) {
    $context_providers = [];
    foreach (array_keys($container->findTaggedServiceIds('context_provider')) as $id) {
      $context_providers[] = $id;
    }

    $definition = $container->getDefinition('context.repository');
    $definition->addArgument($context_providers);
  }

}
