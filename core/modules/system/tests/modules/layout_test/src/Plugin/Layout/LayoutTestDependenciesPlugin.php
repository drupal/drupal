<?php

namespace Drupal\layout_test\Plugin\Layout;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides a plugin that contains config dependencies.
 *
 * @Layout(
 *   id = "layout_test_dependencies_plugin",
 *   label = @Translation("Layout plugin (with dependencies)"),
 *   category = @Translation("Layout test"),
 *   description = @Translation("Test layout"),
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     }
 *   }
 * )
 */
class LayoutTestDependenciesPlugin extends LayoutDefault implements DependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    $dependencies['config'][] = 'system.menu.myothermenu';
    return $dependencies;
  }

}
