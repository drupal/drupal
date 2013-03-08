<?php

/**
 * @file
 * Definition of Drupal\field_test\Plugin\field\formatter\TestFieldMultipleFormatter.
 */

namespace Drupal\field_test\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'field_test_multiple' formatter.
 *
 * @Plugin(
 *   id = "field_test_multiple",
 *   module = "field_test",
 *   label = @Translation("Multiple"),
 *   description = @Translation("Multiple formatter"),
 *   field_types = {
 *     "test_field"
 *   },
 *   settings = {
 *     "test_formatter_setting_multiple" = "dummy test string"
 *   }
 * )
 */
class TestFieldMultipleFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['test_formatter_setting_multiple'] = array(
      '#title' => t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('test_formatter_setting_multiple'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsSummary() {
    return t('@setting: @value', array('@setting' => 'test_formatter_setting_multiple', '@value' => $this->getSetting('test_formatter_setting_multiple')));
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    if (!empty($items)) {
      $array = array();
      foreach ($items as $delta => $item) {
        $array[] = $delta . ':' . $item['value'];
      }
      $elements[0] = array('#markup' => $this->getSetting('test_formatter_setting_multiple') . '|' . implode('|', $array));
    }

    return $elements;
  }
}
