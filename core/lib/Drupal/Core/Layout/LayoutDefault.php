<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\ConfigurableTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a default class for Layout plugins.
 */
class LayoutDefault extends PluginBase implements LayoutInterface {

  use ConfigurableTrait;

  /**
   * The layout definition.
   *
   * @var \Drupal\Core\Layout\LayoutDefinition
   */
  protected $pluginDefinition;

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
