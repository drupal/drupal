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
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_default",
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
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, FieldInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      // @todo Convert text_sanitize() to work on an NG $item. See
      // https://drupal.org/node/2026339.
      $itemBC = $item->getValue(TRUE);
      $output = text_sanitize($this->getFieldSetting('text_processing'), $langcode, $itemBC, 'value');
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
