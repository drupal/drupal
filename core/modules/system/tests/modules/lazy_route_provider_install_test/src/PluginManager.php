<?php

declare(strict_types=1);

namespace Drupal\lazy_route_provider_install_test;

use Drupal\Component\Plugin\Attribute\PluginID;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;

/**
 * Plugin manager for testing the lazy route provider during module install.
 */
class PluginManager extends DefaultPluginManager {

  /**
   * PluginManager constructor.
   *
   * This plugin manager depends on the URL generator to ensure that this
   * service is instantiated during module installation when the plugin caches
   * are cleared.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   A cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, UrlGeneratorInterface $url_generator) {
    // Generate a URL during construction to prove that URL generation works. If
    // the route was missing an exception would be thrown. This also forces the
    // route provider to be initialized very early during a module install.
    \Drupal::state()->set(__CLASS__, Url::fromRoute('system.admin')->toString());
    parent::__construct('Plugin/LazyRouteProviderInstallTest', $namespaces, $module_handler, NULL, PluginID::class);
  }

}
