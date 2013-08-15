<?php

/**
 * @file
 * Contains \Drupal\filter\FilterPluginManager.
 */

namespace Drupal\filter;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Manages text processing filters.
 *
 * @see hook_filter_info_alter()
 */
class FilterPluginManager extends PluginManagerBase {

  /**
   * Constructs a FilterPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces) {
    $annotation_namespaces = array('Drupal\filter\Annotation' => $namespaces['Drupal\filter']);
    $this->discovery = new AnnotatedClassDiscovery('Plugin/Filter', $namespaces, $annotation_namespaces, 'Drupal\filter\Annotation\Filter');
    $this->discovery = new AlterDecorator($this->discovery, 'filter_info');
    $cache_key = 'filter_plugins:' . language(Language::TYPE_INTERFACE)->id;
    $cache_tags = array('filter_formats' => TRUE);
    $this->discovery = new CacheDecorator($this->discovery, $cache_key, 'cache', CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    $this->factory = new ContainerFactory($this);
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
