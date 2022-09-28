<?php

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_test_multiple' formatter.
 *
 * @FieldFormatter(
 *   id = "field_test_multiple",
 *   label = @Translation("Multiple"),
 *   description = @Translation("Multiple formatter"),
 *   field_types = {
 *     "test_field",
 *     "test_field_with_preconfigured_options"
 *   },
 *   weight = 5
 * )
 */
class TestFieldMultipleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'test_formatter_setting_multiple' => 'dummy test string',
      'alter' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['test_formatter_setting_multiple'] = [
      '#title' => $this->t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('test_formatter_setting_multiple'),
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('@setting: @value', ['@setting' => 'test_formatter_setting_multiple', '@value' => $this->getSetting('test_formatter_setting_multiple')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if (!empty($items)) {
      $array = [];
      foreach ($items as $delta => $item) {
        $array[] = $delta . ':' . $item->value;
      }
      $elements[0] = ['#markup' => $this->getSetting('test_formatter_setting_multiple') . '|' . implode('|', $array)];
    }

    return $elements;
  }

}
