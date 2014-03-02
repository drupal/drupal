<?php

/**
 * @file
 * Contains \Drupal\filter\FilterPluginManager.
 */

namespace Drupal\filter;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Manages text processing filters.
 *
 * @see hook_filter_info_alter()
 */
class FilterPluginManager extends DefaultPluginManager {

  /**
   * Constructs a FilterPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Filter', $namespaces, $module_handler, 'Drupal\filter\Annotation\Filter');
    $this->alterInfo('filter_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'filter_plugins', array('filter_formats' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id) {
    $plugins = $this->getDefinitions();
    // If the requested filter is missing, use the null filter.
    return isset($plugins[$plugin_id]) ? $plugins[$plugin_id] : $plugins['filter_null'];
  }

}
