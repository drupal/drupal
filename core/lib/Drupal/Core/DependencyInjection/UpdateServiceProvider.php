<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\UpdateServiceProvider.
 */

namespace Drupal\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * ServiceProvider class for update.php service overrides.
 *
 * This class is manually added by update.php via $conf['container_service_providers']
 * and required to prevent various services from trying to retrieve data from
 * storages that do not exist yet.
 */
class UpdateServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
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
