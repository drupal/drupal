<?php

/**
 * @file
 *
 * Contains \Drupal\Core\Entity\Field\FieldTypePluginManager.
 */

namespace Drupal\Core\Entity\Field;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\field\Plugin\Type\FieldType\LegacyFieldTypeDiscoveryDecorator;

/**
 * Plugin manager for 'field type' plugins.
 */
class FieldTypePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'settings' => array(),
    'instance_settings' => array(),
    'list_class' => '\Drupal\field\Plugin\Type\FieldType\ConfigFieldItemList',
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
    parent::__construct('Plugin/field/field_type', $namespaces, 'Drupal\Core\Entity\Annotation\FieldType');
    $this->alterInfo($module_handler, 'field_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'field_types');

    // @todo Remove once all core field types have been converted (see
    // http://drupal.org/node/2014671).
    $this->discovery = new LegacyFieldTypeDiscoveryDecorator($this->discovery, $module_handler);
  }

  /**
   * Returns the default field-level settings for a field type.
   *
   * @param string $type
   *   A field type name.
   *
   * @return array
   *   The type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['settings']) ? $info['settings'] : array();
  }

  /**
   * Returns the default instance-level settings for a field type.
   *
   * @param string $type
   *   A field type name.
   *
   * @return array
   *   The instance's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultInstanceSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['instance_settings']) ? $info['instance_settings'] : array();
  }

}
