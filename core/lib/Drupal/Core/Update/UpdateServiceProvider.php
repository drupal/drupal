<?php

namespace Drupal\Core\Update;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Ensures for some services that they don't cache.
 */
class UpdateServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $definition = new Definition('Drupal\Core\Cache\NullBackend', ['null']);
    $container->setDefinition('cache.null', $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $null_cache_service = new Reference('cache.null');

    $definition = $container->getDefinition('asset.resolver');
    $definition->replaceArgument(5, $null_cache_service);

    $definition = $container->getDefinition('library.discovery.collector');
    $definition->replaceArgument(0, $null_cache_service);

    $definition = $container->getDefinition('theme.registry');
    $definition->replaceArgument(1, $null_cache_service);
    $definition->replaceArgument(7, $null_cache_service);

    $definition = $container->getDefinition('theme.initialization');
    $definition->replaceArgument(2, $null_cache_service);

    $definition = $container->getDefinition('plugin.manager.element_info');
    $definition->replaceArgument(1, $null_cache_service);

    // Prevent the alias-based path processor, which requires a path_alias db
    // table, from being registered to the path processor manager. We do this by
    // removing the tags that the compiler pass looks for. This means the url
    // generator can safely be used during the database update process.
    if ($container->hasDefinition('path_processor_alias')) {
      $container->getDefinition('path_processor_alias')
        ->clearTag('path_processor_inbound')
        ->clearTag('path_processor_outbound');
    }

    // Loop over the defined services and remove any with unmet dependencies.
    // The kernel cannot be booted if the container has such services. This
    // allows modules to run their update hooks to enable newly added
    // dependencies.
    do {
      $definitions = $container->getDefinitions();
      foreach ($definitions as $key => $definition) {
        foreach ($definition->getArguments() as $argument) {
          if ($argument instanceof Reference) {
            if (!$container->has((string) $argument) && $argument->getInvalidBehavior() === ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
              // If the container does not have the argument and would throw an
              // exception, remove the service.
              $container->removeDefinition($key);
            }
          }
        }
      }
      // Remove any aliases which point to undefined services.
      $aliases = $container->getAliases();
      foreach ($aliases as $key => $alias) {
        if (!$container->has((string) $alias)) {
          $container->removeAlias($key);
        }
      }
      // Repeat if services or aliases have been removed.
    } while (count($definitions) > count($container->getDefinitions()) || count($aliases) > count($container->getAliases()));
  }

}
