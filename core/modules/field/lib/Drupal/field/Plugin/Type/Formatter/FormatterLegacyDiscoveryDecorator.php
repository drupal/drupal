<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Formatter\FormatterLegacyDiscoveryDecorator.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\field\Plugin\Type\LegacyDiscoveryDecorator;

/**
 * Custom decorator to add legacy widgets.
 *
 * Legacy formatters are discovered through the old
 * hook_field_formatter_info() hook and handled by the
 * Drupal\field\Plugin\field\formatter\LegacyFormatter class.
 *
 * @todo Remove once all core formatters have been converted.
 */
class FormatterLegacyDiscoveryDecorator extends LegacyDiscoveryDecorator {

  /**
   * Overrides Drupal\field\Plugin\Type\LegacyDiscoveryDecorator::$hook.
   */
  protected $hook = 'field_formatter_info';

  /**
   * Overrides Drupal\field\Plugin\Type\LegacyDiscoveryDecorator::processDefinition().
   */
  public function processDefinition(array &$definition) {
    $definition['class'] = '\Drupal\field\Plugin\field\formatter\LegacyFormatter';

    // Transform properties for which the format has changed.
    if (isset($definition['field types'])) {
      $definition['field_types'] = $definition['field types'];
      unset($definition['field types']);
    }
  }

}
