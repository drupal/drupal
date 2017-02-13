<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a default class for Layout plugins.
 *
 * @internal
 *   The layout system is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class LayoutDefault extends PluginBase implements LayoutInterface {

  /**
   * The layout definition.
   *
   * @var \Drupal\Core\Layout\LayoutDefinition
   */
  protected $pluginDefinition;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    // Ensure $build only contains defined regions and in the order defined.
    $build = [];
    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      if (array_key_exists($region_name, $regions)) {
        $build[$region_name] = $regions[$region_name];
      }
    }
    $build['#settings'] = $this->getConfiguration();
    $build['#layout'] = $this->pluginDefinition;
    $build['#theme'] = $this->pluginDefinition->getThemeHook();
    if ($library = $this->pluginDefinition->getLibrary()) {
      $build['#attached']['library'][] = $library;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Layout\LayoutDefinition
   */
  public function getPluginDefinition() {
    return parent::getPluginDefinition();
  }

}
