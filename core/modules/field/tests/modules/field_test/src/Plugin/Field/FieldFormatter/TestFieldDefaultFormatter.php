<?php

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'field_test_default' formatter.
 */
#[FieldFormatter(
  id: 'field_test_default',
  label: new TranslatableMarkup('Default'),
  description: new TranslatableMarkup('Default formatter'),
  field_types: [
    'test_field',
    'test_field_with_preconfigured_options',
  ],
  weight: 1,
)]
class TestFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'test_formatter_setting' => 'dummy test string',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['test_formatter_setting'] = [
      '#title' => $this->t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('test_formatter_setting'),
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('@setting: @value', ['@setting' => 'test_formatter_setting', '@value' => $this->getSetting('test_formatter_setting')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->getSetting('test_formatter_setting') . '|' . $item->value];
    }

    return $elements;
  }

}
