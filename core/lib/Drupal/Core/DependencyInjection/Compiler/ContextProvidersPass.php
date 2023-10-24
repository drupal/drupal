<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds the context provider service IDs to the context manager.
 *
 * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. The
 *    context.repository service uses the service_id_collector pattern instead
 *    so this is no longer needed.
 *
 * @see https://www.drupal.org/node/3395641
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
