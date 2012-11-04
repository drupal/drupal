<?php

/**
 * @file
 * Definition of Drupal\email\Plugin\field\widget\EmailDefaultWidget.
 */

namespace Drupal\email\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'email_default' widget.
 *
 * @Plugin(
 *   id = "email_default",
 *   module = "email",
 *   label = @Translation("E-mail"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class EmailDefaultWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element['value'] = $element + array(
      '#type' => 'email',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL,
    );
    return $element;
  }

}
