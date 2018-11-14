<?php

namespace Drupal\Core\Update;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
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
    $definition = $container->getDefinition('asset.resolver');
    $argument = new Reference('cache.null');
    $definition->replaceArgument(5, $argument);

    $definition = $container->getDefinition('library.discovery.collector');
    $argument = new Reference('cache.null');
    $definition->replaceArgument(0, $argument);

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
