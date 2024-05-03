<?php

namespace Drupal\layout_test\Plugin\Layout;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a plugin that contains config dependencies.
 */
#[Layout(
  id: 'layout_test_dependencies_plugin',
  label: new TranslatableMarkup('Layout plugin (with dependencies)'),
  category: new TranslatableMarkup('Layout test'),
  description: new TranslatableMarkup('Test layout'),
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region"),
    ],
  ],
)]
class LayoutTestDependenciesPlugin extends LayoutDefault implements DependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    $dependencies['config'][] = 'system.menu.my-other-menu';
    return $dependencies;
  }

}
