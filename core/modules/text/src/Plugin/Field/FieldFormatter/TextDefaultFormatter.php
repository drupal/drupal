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
      // The viewElements() method of entity field formatters is run
      // during the #pre_render phase of rendering an entity. A formatter
      // builds the content of the field in preparation for theming.
      // All cache tags must be available after the #pre_render phase. In order
      // to collect the cache tags associated with the processed text, it must
      // be passed to drupal_render() so that its #pre_render callback is
      // invoked and its full build array is assembled. Rendering the processed
      // text in place here will allow its cache tags to be bubbled up and
      // included with those of the main entity when cache tags are collected
      // for a renderable array in drupal_render().
      // @todo remove this work-around, see https://drupal.org/node/2273277
      drupal_render($elements[$delta], TRUE);
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
