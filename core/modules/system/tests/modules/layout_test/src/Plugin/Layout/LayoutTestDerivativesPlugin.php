<?php

namespace Drupal\layout_test\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;

/**
 * @todo.
 *
 * @Layout(
 *   id = "layout_test_derivatives_plugin",
 *   label = @Translation("Layout plugin (with derivatives)"),
 *   category = @Translation("Layout test"),
 *   description = @Translation("Test layout"),
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     }
 *   },
 *   deriver = "\Drupal\layout_test\Plugin\Derivative\LayoutTestPluginDeriver",
 * )
 */
class LayoutTestDerivativesPlugin extends LayoutDefault {

}
