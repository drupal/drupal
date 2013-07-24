<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Type\SelectionPluginManager.
 */

namespace Drupal\entity_reference\Plugin\Type;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\entity_reference\Plugin\Type\Selection\SelectionBroken;

/**
 * Plugin type manager for the Entity Reference Selection plugin.
 */
class SelectionPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    $annotation_namespaces = array('Drupal\entity_reference\Annotation' => $namespaces['Drupal\entity_reference']);
    $this->discovery = new AnnotatedClassDiscovery('Plugin/entity_reference/selection', $namespaces, $annotation_namespaces, 'Drupal\entity_reference\Annotation\EntityReferenceSelection');

    // We're not using the parent constructor because we use a different factory
    // method and don't need the derivative discovery decorator.
    $this->factory = new ReflectionFactory($this);

    $this->alterInfo($module_handler, 'entity_reference_selection');
    $this->setCacheBackend($cache_backend, $language_manager, 'entity_reference_selection');
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // We want to provide a broken handler class whenever a class is not found.
    try {
      return parent::createInstance($plugin_id, $configuration);
    }
    catch (PluginException $e) {
      return new SelectionBroken($configuration['field_definition']);
    }
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options) {
    $selection_handler = $options['field_definition']->getFieldSetting('handler');
    $target_entity_type = $options['field_definition']->getFieldSetting('target_type');

    // Get all available selection plugins for this entity type.
    $selection_handler_groups = $this->getSelectionGroups($target_entity_type);

    // Sort the selection plugins by weight and select the best match.
    uasort($selection_handler_groups[$selection_handler], 'drupal_sort_weight');
    end($selection_handler_groups[$selection_handler]);
    $plugin_id = key($selection_handler_groups[$selection_handler]);

    return $this->createInstance($plugin_id, $options);
  }

  /**
   * Returns a list of selection plugins that can reference a specific entity
   * type.
   *
   * @param string $entity_type
   *   A Drupal entity type.
   *
   * @return array
   *   An array of selection plugins grouped by selection group.
   */
  public function getSelectionGroups($entity_type) {
    $plugins = array();

    foreach ($this->getDefinitions() as $plugin_id => $plugin) {
      if (empty($plugin['entity_types']) || in_array($entity_type, $plugin['entity_types'])) {
        $plugins[$plugin['group']][$plugin_id] = $plugin;
      }
    }

    return $plugins;
  }
}
