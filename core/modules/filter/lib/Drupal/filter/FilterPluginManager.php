<?php

/**
 * @file
 * Contains \Drupal\filter\FilterPluginManager.
 */

namespace Drupal\filter;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

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
    $this->discovery = new AnnotatedClassDiscovery('Filter', $namespaces, $annotation_namespaces, 'Drupal\filter\Annotation\Filter');
    $this->discovery = new AlterDecorator($this->discovery, 'filter_info');
    $this->discovery = new CacheDecorator($this->discovery, 'filter_plugins:' . language(Language::TYPE_INTERFACE)->langcode, 'cache', array(
      'filter_formats' => TRUE,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array(), FilterBag $filter_bag = NULL) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    return new $plugin_class($configuration, $plugin_id, $plugin_definition, $filter_bag);
  }

}
