<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldTypePluginManager.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for 'field type' plugins.
 *
 * @ingroup field_types
 */
class FieldTypePluginManager extends DefaultPluginManager implements FieldTypePluginManagerInterface {

  /**
   * Constructs the FieldTypePluginManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Field/FieldType', $namespaces, $module_handler, 'Drupal\Core\Field\Annotation\FieldType');
    $this->alterInfo('field_info');
    $this->setCacheBackend($cache_backend, 'field_types_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (!isset($definition['list_class'])) {
      $definition['list_class'] = '\Drupal\Core\Field\FieldItemList';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultSettings();
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInstanceSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultInstanceSettings();
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getUiDefinitions() {
    $definitions = $this->getDefinitions();
    return array_filter($definitions, function ($definition) {
      return empty($definition['no_ui']) && !empty($definition['default_formatter']) && !empty($definition['default_widget']);
    });
  }

  /**
   * @inheritdoc
   */
  public function getPluginClass($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    return $plugin_definition['class'];
  }

}
