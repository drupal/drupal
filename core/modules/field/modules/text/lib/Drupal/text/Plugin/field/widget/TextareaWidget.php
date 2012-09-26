<?php

/**
 * @file
 * Definition of Drupal\text\Plugin\field\widget\TextareaWidget.
 */

namespace Drupal\text\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'text_textarea' widget.
 *
 * @Plugin(
 *   id = "text_textarea",
 *   module = "text",
 *   label = @Translation("Text area (multiple rows)"),
 *   field_types = {
 *     "text_long"
 *   },
 *   settings = {
 *     "rows" = "5"
 *   }
 * )
 */
class TextareaWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['rows'] = array(
      '#type' => 'number',
      '#title' => t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#required' => TRUE,
      '#min' => 1,
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $main_widget = $element + array(
      '#type' => 'textarea',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL,
      '#rows' => $this->getSetting('rows'),
      '#attributes' => array('class' => array('text-full')),
    );

    if ($this->instance['settings']['text_processing']) {
      $element = $main_widget;
      $element['#type'] = 'text_format';
      $element['#format'] = isset($items[$delta]['format']) ? $items[$delta]['format'] : NULL;
      $element['#base_type'] = $main_widget['#type'];
    }
    else {
      $element['value'] = $main_widget;
    }

    return $element;
  }

}
