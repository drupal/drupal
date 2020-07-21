<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Cache\MemoryBackendFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\Lock\NullLockBackend;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for the installer environment.
 *
 * This class is manually added by install_begin_request() via
 * $conf['container_service_providers'] and optimizes the container for
 * installation.
 *
 * Note that the early installer environment requires the further customizations
 * in InstallerServiceProvider.
 *
 * @see install_begin_request()
 * @see \Drupal\Core\Installer\InstallerServiceProvider
 */
class NormalInstallerServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Replace cache services with in-memory implementations. The results in
    // less queries to set caches which will only be cleared on the next module
    // install.
    $definition = $container->getDefinition('cache_factory');
    $definition->setClass(MemoryBackendFactory::class);
    $definition->setArguments([]);
    $definition->setMethodCalls([]);

    // Replace lock service with no-op implementation as Drupal installation can
    // only occur in a single thread and the site should not be publicly
    // available.
    $container
      ->register('lock', NullLockBackend::class);

    // Remove the cache tags invalidator tag from the cache tags storage, so
    // that we don't call it when cache tags are invalidated in the installer.
    $container->getDefinition('cache_tags.invalidator.checksum')
      ->clearTag('cache_tags_invalidator');

    // Use performance-optimized extension lists.
    $container->getDefinition('extension.list.module')->setClass(InstallerModuleExtensionList::class);
    $container->getDefinition('extension.list.theme')->setClass(InstallerThemeExtensionList::class);
    $container->getDefinition('extension.list.theme_engine')->setClass(InstallerThemeEngineExtensionList::class);

    // Don't register the lazy route provider in the super early installer.
    if (get_called_class() === NormalInstallerServiceProvider::class) {
      $lazy_route_provider = $container->register('router.route_provider.installer');
      $lazy_route_provider
        ->setClass(InstallerRouteProviderLazyBuilder::class)
        ->setDecoratedService('router.route_provider')
        ->addArgument(new Reference('router.route_provider.installer.inner'))
        ->addArgument(new Reference('router.builder'))
        ->addTag('event_subscriber');
    }
  }

}
