<?php

namespace Drupal\field_layout_test\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides an annotated layout plugin for field_layout tests.
 *
 * @Layout(
 *   id = "test_layout_main_and_footer",
 *   label = @Translation("Test plugin: Main and Footer"),
 *   category = @Translation("Layout test"),
 *   description = @Translation("Test layout"),
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     },
 *     "footer" = {
 *       "label" = @Translation("Footer Region")
 *     }
 *   },
 *   config_dependencies = {
 *     "module" = {
 *       "layout_discovery",
 *     },
 *   },
 * )
 */
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
