<?php

/**
 * @file
 *
 * Definition of Drupal\text\Plugin\field\formatter\TextTrimmedFormatter.
 */
namespace Drupal\text\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'text_trimmed'' formatter.
 *
 * Note: This class also contains the implementations used by the
 * 'text_summary_or_trimmed' formatter.
 *
 * @see Drupal\text\Field\Formatter\TextSummaryOrTrimmedFormatter
 *
 * @Plugin(
 *   id = "text_trimmed",
 *   module = "text",
 *   label = @Translation("Trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "trim_length" = "600"
 *   },
 *   edit = {
 *     "editor" = "form"
 *   }
 * )
 */
class TextTrimmedFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['trim_length'] = array(
      '#title' => t('Trim length'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsSummary().
   */
  public function settingsSummary() {
    return t('Trim length: @trim_length', array(
      '@trim_length' => $this->getSetting('trim_length'),
    ));
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($this->getPluginId() == 'text_summary_or_trimmed' && !empty($item['summary'])) {
        $output = _text_sanitize($this->instance, $langcode, $item, 'summary');
      }
      else {
        $output = _text_sanitize($this->instance, $langcode, $item, 'value');
        $output = text_summary($output, $this->instance['settings']['text_processing'] ? $item['format'] : NULL, $this->getSetting('trim_length'));
      }
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
