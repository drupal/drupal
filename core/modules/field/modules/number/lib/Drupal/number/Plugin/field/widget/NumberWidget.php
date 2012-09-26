<?php

/**
 * @file
 * Definition of Drupal\number\Plugin\field\widget\NumberWidget.
 */

namespace Drupal\number\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'number' widget.
 *
 * @Plugin(
 *   id = "number",
 *   module = "number",
 *   label = @Translation("Text field"),
 *   field_types = {
 *     "number_integer",
 *     "number_decimal",
 *     "number_float"
 *   }
 * )
 */
class NumberWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $field = $this->field;
    $instance = $this->instance;

    $value = isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL;

    $element += array(
      '#type' => 'number',
      '#default_value' => $value,
    );

    // Set the step for floating point and decimal numbers.
    switch ($field['type']) {
      case 'number_decimal':
        $element['#step'] = pow(0.1, $field['settings']['scale']);
        break;

      case 'number_float':
        $element['#step'] = 'any';
        break;
    }

    // Set minimum and maximum.
    if (is_numeric($instance['settings']['min'])) {
      $element['#min'] = $instance['settings']['min'];
    }
    if (is_numeric($instance['settings']['max'])) {
      $element['#max'] = $instance['settings']['max'];
    }

    // Add prefix and suffix.
    if (!empty($instance['settings']['prefix'])) {
      $prefixes = explode('|', $instance['settings']['prefix']);
      $element['#field_prefix'] = field_filter_xss(array_pop($prefixes));
    }
    if (!empty($instance['settings']['suffix'])) {
      $suffixes = explode('|', $instance['settings']['suffix']);
      $element['#field_suffix'] = field_filter_xss(array_pop($suffixes));
    }

    return array('value' => $element);
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::errorElement().
   */
  public function errorElement(array $element, array $error, array $form, array &$form_state) {
    return $element['value'];
  }

}
