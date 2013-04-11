<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\field\widget\HiddenWidget.
 */

namespace Drupal\field\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'Hidden' widget.
 *
 * @Plugin(
 *   id = "hidden",
 *   module = "field",
 *   label = @Translation("- Hidden -"),
 *   multiple_values = TRUE,
 *   weight = 50
 * )
 */
class HiddenWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    // The purpose of this widget is to be hidden, so nothing to do here.
    return array();
  }
}
