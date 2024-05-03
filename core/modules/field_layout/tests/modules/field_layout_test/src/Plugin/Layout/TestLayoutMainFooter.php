<?php

namespace Drupal\field_layout_test\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an attributed layout plugin for field_layout tests.
 */
#[Layout(
  id: 'test_layout_main_and_footer',
  label: new TranslatableMarkup('Test plugin: Main and Footer'),
  category: new TranslatableMarkup('Layout test'),
  description: new TranslatableMarkup('Test layout'),
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region"),
    ],
    "footer" => [
      "label" => new TranslatableMarkup("Footer Region"),
    ],
  ],
  config_dependencies: [
    "module" => [
      "layout_discovery",
    ],
  ],
)]
class TestLayoutMainFooter extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'setting_1' => 'Default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = 'system';
    return $dependencies;
  }

}
