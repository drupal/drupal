<?php

/**
 * @file
 * Definition of Drupal\text\Plugin\field\formatter\TextDefaultFormatter.
 */

namespace Drupal\text\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_default",
 *   module = "text",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   edit = {
 *     "editor" = "direct"
 *   }
 * )
 */
class TextDefaultFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $output = text_sanitize($this->getFieldSetting('text_processing'), $langcode, $item, 'value');
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
