<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Type\ResourcePluginManager.
 */

namespace Drupal\rest\Plugin\Type;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of resource plugins.
 */
class ResourcePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new \Drupal\rest\Plugin\Type\ResourcePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/rest/resource', $namespaces, $module_handler, 'Drupal\rest\Annotation\RestResource');

    $this->setCacheBackend($cache_backend, $language_manager, 'rest_plugins');
    $this->alterInfo('rest_resource');
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options){
    if (isset($options['id'])) {
      return $this->createInstance($options['id']);
    }
  }
}
