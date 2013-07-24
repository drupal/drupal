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
    'list_class' => '\Drupal\field\Plugin\Type\FieldType\ConfigField',
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
    $annotation_namespaces = array(
      'Drupal\Core\Entity\Annotation' => DRUPAL_ROOT . '/core/lib',
    );
    parent::__construct('Plugin/field/field_type', $namespaces, $annotation_namespaces, 'Drupal\Core\Entity\Annotation\FieldType');
    $this->alterInfo($module_handler, 'field_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'field_types');

    // @todo Remove once all core field types have been converted (see
    // http://drupal.org/node/2014671).
    $this->discovery = new LegacyFieldTypeDiscoveryDecorator($this->discovery, $module_handler);
  }

}
