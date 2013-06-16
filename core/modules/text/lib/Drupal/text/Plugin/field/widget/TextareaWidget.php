<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\widget\TextareaWidget.
 */

namespace Drupal\text\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
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
 *     "rows" = "5",
 *     "placeholder" = ""
 *   }
 * )
 */
class TextareaWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['rows'] = array(
      '#type' => 'number',
      '#title' => t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $main_widget = $element + array(
      '#type' => 'textarea',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : NULL,
      '#rows' => $this->getSetting('rows'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#attributes' => array('class' => array('text-full')),
    );

    if ($this->getFieldSetting('text_processing')) {
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
