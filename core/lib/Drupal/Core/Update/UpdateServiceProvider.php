<?php

namespace Drupal\Core\Update;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Customises the container for running updates.
 */
class UpdateServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $definition = new Definition('Drupal\Core\Cache\NullBackend', ['null']);
    $container->setDefinition('cache.null', $definition);

    $container->addCompilerPass(new UpdateCompilerPass(), PassConfig::TYPE_REMOVE, 128);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Ensures for some services that they don't cache.
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
  }

}
