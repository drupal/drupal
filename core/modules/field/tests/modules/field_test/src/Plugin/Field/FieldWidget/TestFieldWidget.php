<?php

/**
 * @file
 * Definition of Drupal\field_test\Plugin\Field\FieldWidget\TestFieldWidget.
 */

namespace Drupal\field_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'test_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "test_field_widget",
 *   label = @Translation("Test widget"),
 *   field_types = {
 *     "test_field",
 *     "hidden_test_field",
 *     "test_field_with_preconfigured_options"
 *   },
 *   weight = -10
 * )
 */
class TestFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'test_widget_setting' => 'dummy test string',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('@setting: @value', array('@setting' => 'test_widget_setting', '@value' => $this->getSetting('test_widget_setting')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
    );
    return array('value' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

}
