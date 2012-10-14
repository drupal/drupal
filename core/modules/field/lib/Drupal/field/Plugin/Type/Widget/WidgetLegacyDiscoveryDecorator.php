<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Widget\WidgetLegacyDiscoveryDecorator.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\field\Plugin\Type\LegacyDiscoveryDecorator;

/**
 * Custom decorator to add legacy widgets.
 *
 * Legacy widgets are discovered through the old hook_field_widget_info() hook,
 * and handled by the Drupal\field\Plugin\field\widget\LegacyWidget class.
 *
 * @todo Remove once all core widgets have been converted.
 */
class WidgetLegacyDiscoveryDecorator extends LegacyDiscoveryDecorator {

  /**
   * Overrides Drupal\field\Plugin\Type\LegacyDiscoveryDecorator::$hook.
   */
  protected $hook = 'field_widget_info';

  /**
   * Overrides Drupal\field\Plugin\Type\LegacyDiscoveryDecorator::processDefinition().
   */
  public function processDefinition(array &$definition) {
    $definition['class'] = '\Drupal\field\Plugin\field\widget\LegacyWidget';

    // Transform properties for which the format has changed.
    if (isset($definition['field types'])) {
      $definition['field_types'] = $definition['field types'];
      unset($definition['field types']);
    }
    if (isset($definition['behaviors']['multiple values'])) {
      $definition['multiple_values'] = $definition['behaviors']['multiple values'];
      unset($definition['behaviors']['multiple values']);
    }
  }

}
