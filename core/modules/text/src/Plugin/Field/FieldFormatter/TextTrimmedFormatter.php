<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\formatter\TextTrimmedFormatter.
 */
namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_trimmed' formatter.
 *
 * Note: This class also contains the implementations used by the
 * 'text_summary_or_trimmed' formatter.
 *
 * @see \Drupal\text\Field\Formatter\TextSummaryOrTrimmedFormatter
 *
 * @FieldFormatter(
 *   id = "text_trimmed",
 *   label = @Translation("Trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class TextTrimmedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '600',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Trim length: @trim_length', array('@trim_length' => $this->getSetting('trim_length')));
    return $summary;
  }

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
        '#text' => NULL,
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
      if ($this->getPluginId() == 'text_summary_or_trimmed' && !empty($item->summary)) {
        $elements[$delta]['#text'] = $item->summary;
        // @todo remove this work-around, see https://drupal.org/node/2273277
        drupal_render($elements[$delta], TRUE);
      }
      else {
        $elements[$delta]['#text'] = $item->value;
        // @todo remove this work-around, see https://drupal.org/node/2273277
        drupal_render($elements[$delta], TRUE);
        $elements[$delta]['#markup'] = text_summary($elements[$delta]['#markup'], $item->format, $this->getSetting('trim_length'));
      }
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
      if ($this->getPluginId() == 'text_summary_or_trimmed' && !empty($item->summary)) {
        $output = $item->summary_processed;
      }
      else {
        $output = text_summary($item->processed, NULL, $this->getSetting('trim_length'));
      }

      $elements[$delta] = array(
        '#markup' => $output,
      );
    }

    return $elements;
  }

}
