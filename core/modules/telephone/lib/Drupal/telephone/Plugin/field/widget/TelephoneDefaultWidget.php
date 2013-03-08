<?php

/**
 * @file
 * Contains \Drupal\telephone\Plugin\field\widget\TelephoneDefaultWidget.
 */

namespace Drupal\telephone\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'telephone_default' widget.
 *
 * @Plugin(
 *   id = "telephone_default",
 *   module = "telephone",
 *   label = @Translation("Telephone number"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class TelephoneDefaultWidget extends WidgetBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element['value'] = $element + array(
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL,
    );
    return $element;
  }

}
