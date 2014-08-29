<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\formatter\TextDefaultFormatter.
 */

namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class TextDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    if ($this->getFieldSetting('text_processing')) {
      return $this->viewElementsWithTextProcessing($items);
    }
    else {
      return $this->viewElementsWithoutTextProcessing($items);
    }
  }

  /**
   * Builds a renderable array when text processing is enabled.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The text field values to be rendered.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  protected function viewElementsWithTextProcessing(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      );
    }

    return $elements;
  }

  /**
   * Builds a renderable array when text processing is disabled.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The text field values to be rendered.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  protected function viewElementsWithoutTextProcessing(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#markup' => $item->processed,
      );
    }

    return $elements;
  }

}
