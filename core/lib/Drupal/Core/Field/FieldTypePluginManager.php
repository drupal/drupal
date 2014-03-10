<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldTypePluginManager.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for 'field type' plugins.
 */
class FieldTypePluginManager extends DefaultPluginManager implements FieldTypePluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'settings' => array(),
    'instance_settings' => array(),
  );

  /**
   * Constructs the FieldTypePluginManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Field/FieldType', $namespaces, $module_handler, 'Drupal\Core\Field\Annotation\FieldType');
    $this->alterInfo('field_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'field_types_plugins');
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
    $info = $this->getDefinition($type);
    return isset($info['settings']) ? $info['settings'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInstanceSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['instance_settings']) ? $info['instance_settings'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurableDefinitions() {
    $definitions = $this->getDefinitions();
    return array_filter($definitions, function ($definition) {
      return $definition['configurable'];
    });
  }

}
