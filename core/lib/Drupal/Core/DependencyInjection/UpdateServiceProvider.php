<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\UpdateServiceProvider.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;

/**
 * ServiceProvider class for update.php service overrides.
 *
 * This class is manually added by update.php via $conf['container_service_providers']
 * and required to prevent various services from trying to retrieve data from
 * storages that do not exist yet.
 */
class UpdateServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    if (!empty($GLOBALS['conf']['update_service_provider_overrides'])) {
      // Disable the Lock service.
      $container
        ->register('lock', 'Drupal\Core\Lock\NullLockBackend');

      // Prevent config from being accessed via a cache wrapper by removing
      // any existing definition and setting an alias to the actual storage.
      $container->removeDefinition('config.storage');
      $container->setAlias('config.storage', 'config.storage.active');

      $container
        ->register('cache_factory', 'Drupal\Core\Cache\MemoryBackendFactory');
      $container
        ->register('router.builder', 'Drupal\Core\Routing\RouteBuilderStatic');

      $container->register('theme_handler', 'Drupal\Core\Extension\ThemeHandler')
        ->addArgument(new Reference('config.factory'))
        ->addArgument(new Reference('module_handler'))
        ->addArgument(new Reference('state'))
        ->addArgument(new Reference('info_parser'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Ensure that URLs generated for the home and admin pages don't have
    // 'update.php' in them.
    $request = Request::createFromGlobals();
    $definition = $container->getDefinition('url_generator');
    $definition->addMethodCall('setBasePath', array(str_replace('/core', '', $request->getBasePath()) . '/'));
    // We need to set the script path to an empty string since the value
    // determined by \Drupal\Core\Routing\UrlGenerator::setRequest() is invalid
    // once '/core' has been removed from the base path.
    $definition->addMethodCall('setScriptPath', array(''));
  }

}
