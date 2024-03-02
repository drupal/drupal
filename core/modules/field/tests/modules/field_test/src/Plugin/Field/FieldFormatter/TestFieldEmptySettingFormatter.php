<?php

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'field_empty_setting' formatter.
 */
#[FieldFormatter(
  id: 'field_empty_setting',
  label: new TranslatableMarkup('Field empty setting'),
  field_types: [
    'test_field',
  ],
  weight: -1,
)]
class TestFieldEmptySettingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'field_empty_setting' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['field_empty_setting'] = [
      '#title' => $this->t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('field_empty_setting'),
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $setting = $this->getSetting('field_empty_setting');
    if (!empty($setting)) {
      $summary[] = $this->t('Default empty setting now has a value.');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        $elements[$delta] = ['#markup' => $this->getSetting('field_empty_setting')];
      }
    }

    return $elements;
  }

}
