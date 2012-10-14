<?php

/**
 * @file
 * Definition of Drupal\field_test\Plugin\field\formatter\TestFieldDefaultFormatter.
 */

namespace Drupal\field_test\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'field_test_default' formatter.
 *
 * @Plugin(
 *   id = "field_test_default",
 *   module = "field_test",
 *   label = @Translation("Default"),
 *   description = @Translation("Default formatter"),
 *   field_types = {
 *     "test_field"
 *   },
 *   settings = {
 *     "test_formatter_setting" = "dummy test string"
 *   }
 * )
 */
class TestFieldDefaultFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['test_formatter_setting'] = array(
      '#title' => t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('test_formatter_setting'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsSummary() {
    return t('@setting: @value', array('@setting' => 'test_formatter_setting', '@value' => $this->getSetting('test_formatter_setting')));
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $this->getSetting('test_formatter_setting') . '|' . $item['value']);
    }

    return $elements;
  }
}
