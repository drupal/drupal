<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\UpdateBundle.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle class for update.php service overrides.
 *
 * This bundle is manually added by update.php via $conf['container_bundles']
 * and required to prevent various services from trying to retrieve data from
 * storages that do not exist yet.
 */
class UpdateBundle extends Bundle {

  /**
   * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
   */
  public function build(SymfonyContainerBuilder $container) {
    // Disable the Lock service.
    $container
      ->register('lock', 'Drupal\Core\Lock\NullLockBackend');

    // Prevent config from accessing {cache_config}.
    // @see $conf['cache_classes'], update_prepare_d8_bootstrap()
    $container
      ->register('config.storage', 'Drupal\Core\Config\FileStorage')
      ->addArgument(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
    $container->register('module_handler', 'Drupal\Core\Extension\UpdateModuleHandler')
      ->addArgument('%container.modules%');
    $container
      ->register("cache_factory", 'Drupal\Core\Cache\MemoryBackendFactory');
    $container
      ->register('router.builder', 'Drupal\Core\Routing\RouteBuilderStatic');
  }

}
