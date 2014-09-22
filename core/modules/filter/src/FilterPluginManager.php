<?php

/**
 * @file
 * Contains \Drupal\filter\FilterPluginManager.
 */

namespace Drupal\filter;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages text processing filters.
 *
 * @see hook_filter_info_alter()
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\filter\Plugin\FilterInterface
 * @see \Drupal\filter\Plugin\FilterBase
 * @see plugin_api
 */
class FilterPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a FilterPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Filter', $namespaces, $module_handler, 'Drupal\filter\Plugin\FilterInterface', 'Drupal\filter\Annotation\Filter');
    $this->alterInfo('filter_info');
    $this->setCacheBackend($cache_backend, 'filter_plugins', array('filter_formats' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'filter_null';
  }

}
