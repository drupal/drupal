<?php

/**
 * @file
 * Definition of Drupal\text\Plugin\field\widget\TextfieldWidget.
 */

namespace Drupal\text\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @Plugin(
 *   id = "text_textfield",
 *   module = "text",
 *   label = @Translation("Text field"),
 *   field_types = {
 *     "text"
 *   },
 *   settings = {
 *     "size" = "60"
 *   }
 * )
 */
class TextfieldWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['size'] = array(
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
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
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL,
      '#size' => $this->getSetting('size'),
      '#maxlength' => $this->field['settings']['max_length'],
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
