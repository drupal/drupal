<?php

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_test_with_prepare_view' formatter.
 *
 * @FieldFormatter(
 *   id = "field_test_with_prepare_view",
 *   label = @Translation("With prepare step"),
 *   description = @Translation("Tests prepareView() method"),
 *   field_types = {
 *     "test_field"
 *   },
 *   weight = 10
 * )
 */
class TestFieldPrepareViewFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'test_formatter_setting_additional' => 'dummy test string',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['test_formatter_setting_additional'] = array(
      '#title' => t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('test_formatter_setting_additional'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('@setting: @value', array('@setting' => 'test_formatter_setting_additional', '@value' => $this->getSetting('test_formatter_setting_additional')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // Don't add anything on empty values.
        if (!$item->isEmpty()) {
          $item->additional_formatter_value = $item->value + 1;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $this->getSetting('test_formatter_setting_additional') . '|' . $item->value . '|' . $item->additional_formatter_value);
    }

    return $elements;
  }

}
