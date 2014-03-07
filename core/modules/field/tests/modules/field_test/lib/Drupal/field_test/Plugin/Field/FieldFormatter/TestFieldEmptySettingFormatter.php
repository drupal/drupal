<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\field\formatter\TestFieldEmptySettingFormatter
 */

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_empty_setting' formatter.
 *
 * @FieldFormatter(
 *   id = "field_empty_setting",
 *   label = @Translation("Field empty setting"),
 *   field_types = {
 *     "test_field",
 *   },
 *   settings = {
 *     "field_empty_setting" = ""
 *   },
 *   weight = -1
 * )
 */
class TestFieldEmptySettingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['field_empty_setting'] = array(
      '#title' => t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('field_empty_setting'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $setting = $this->getSetting('field_empty_setting');
    if (!empty($setting)) {
      $summary[] = t('Default empty setting now has a value.');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        $elements[$delta] = array('#markup' => $this->getSetting('field_empty_setting'));
      }
    }

    return $elements;
  }
}
