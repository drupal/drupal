<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Type\SelectionPluginManager.
 */

namespace Drupal\entity_reference\Plugin\Type;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\entity_reference\Plugin\Type\Selection\SelectionBroken;

/**
 * Plugin type manager for the Entity Reference Selection plugin.
 */
class SelectionPluginManager extends PluginManagerBase {

  /**
   * Constructs a SelectionPluginManager object.
   */
  public function __construct() {
    $this->baseDiscovery = new AlterDecorator(new AnnotatedClassDiscovery('entity_reference', 'selection'), 'entity_reference_selection');
    $this->discovery = new CacheDecorator($this->baseDiscovery, 'entity_reference_selection');
    $this->factory = new ReflectionFactory($this);
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
      return new SelectionBroken($configuration['field'], $configuration['instance']);
    }
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options) {
    $selection_handler = $options['instance']['settings']['handler'];
    $target_entity_type = $options['field']['settings']['target_type'];

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
      if (!isset($plugin['entity_types']) || in_array($entity_type, $plugin['entity_types'])) {
        $plugins[$plugin['group']][$plugin_id] = $plugin;
      }
    }

    return $plugins;
  }
}
