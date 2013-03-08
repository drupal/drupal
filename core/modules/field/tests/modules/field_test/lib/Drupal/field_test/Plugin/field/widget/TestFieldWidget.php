<?php

/**
 * @file
 * Definition of Drupal\field_test\Plugin\field\widget\TestFieldWidget.
 */

namespace Drupal\field_test\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'test_field_widget' widget.
 *
 * @Plugin(
 *   id = "test_field_widget",
 *   module = "field_test",
 *   label = @Translation("Test widget"),
 *   field_types = {
 *      "test_field",
 *      "hidden_test_field"
 *   },
 *   settings = {
 *     "test_widget_setting" = "dummy test string"
 *   }
 * )
 */
class TestFieldWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['test_widget_setting'] = array(
      '#type' => 'textfield',
      '#title' => t('Field test field widget setting'),
      '#description' => t('A dummy form element to simulate field widget setting.'),
      '#default_value' => $this->getSetting('test_widget_setting'),
      '#required' => FALSE,
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element += array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : '',
    );
    return array('value' => $element);
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::errorElement().
   */
  public function errorElement(array $element, array $error, array $form, array &$form_state) {
    return $element['value'];
  }

}
