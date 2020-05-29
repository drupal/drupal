<?php

namespace Drupal\Core\Update;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Customizes the container for running updates.
 */
class UpdateServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $definition = new Definition('Drupal\Core\Cache\NullBackend', ['null']);
    $definition->setDeprecated(TRUE, 'The "%service_id%\" service is deprecated. While updating Drupal all caches use \Drupal\Core\Update\UpdateBackend. See https://www.drupal.org/node/3066407');
    $container->setDefinition('cache.null', $definition);

    // Decorate the cache factory in order to use
    // \Drupal\Core\Update\UpdateBackend while running updates.
    $container
      ->register('update.cache_factory', UpdateCacheBackendFactory::class)
      ->setDecoratedService('cache_factory')
      ->addArgument(new Reference('update.cache_factory.inner'));

    $container->addCompilerPass(new UpdateCompilerPass(), PassConfig::TYPE_REMOVE, 128);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // The alias-based processor requires the path_alias entity schema to be
    // installed, so we prevent it from being registered to the path processor
    // manager. We do this by removing the tags that the compiler pass looks
    // for. This means that the URL generator can safely be used during the
    // database update process.
    if ($container->hasDefinition('path_alias.path_processor')) {
      $container->getDefinition('path_alias.path_processor')
        ->clearTag('path_processor_inbound')
        ->clearTag('path_processor_outbound');
    }
  }

}
