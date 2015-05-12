<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\FieldFormatter\TextTrimmedFormatter.
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
      '#title' => t('Trimmed limit'),
      '#type' => 'number',
      '#field_suffix' => t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#description' => t('If the summary is not set, the trimmed %label field will end at the last full sentence before this character limit.', array('%label' => $this->fieldDefinition->getLabel())),
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
    $summary[] = t('Trimmed limit: @trim_length characters', array('@trim_length' => $this->getSetting('trim_length')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    $render_as_summary = function (&$element) {
      // Make sure any default #pre_render callbacks are set on the element,
      // because text_pre_render_summary() must run last.
      $element += \Drupal::service('element_info')->getInfo($element['#type']);
      // Add the #pre_render callback that renders the text into a summary.
      $element['#pre_render'][] = '\Drupal\text\Plugin\field\FieldFormatter\TextTrimmedFormatter::preRenderSummary';
      // Pass on the trim length to the #pre_render callback via a property.
      $element['#text_summary_trim_length'] = $this->getSetting('trim_length');
    };

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => NULL,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      );

      if ($this->getPluginId() == 'text_summary_or_trimmed' && !empty($item->summary)) {
        $elements[$delta]['#text'] = $item->summary;
      }
      else {
        $elements[$delta]['#text'] = $item->value;
        $render_as_summary($elements[$delta]);
      }
    }

    return $elements;
  }

  /**
   * Pre-render callback: Renders a processed text element's #markup as a summary.
   *
   * @param array $element
   *   A structured array with the following key-value pairs:
   *   - #markup: the filtered text (as filtered by filter_pre_render_text())
   *   - #format: containing the machine name of the filter format to be used to
   *     filter the text. Defaults to the fallback format. See
   *     filter_fallback_format().
   *   - #text_summary_trim_length: the desired character length of the summary
   *     (used by text_summary())
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup' trimmed.
   *
   * @see filter_pre_render_text()
   * @see text_summary()
   */
  public static function preRenderSummary(array $element) {
    $element['#markup'] = text_summary($element['#markup'], $element['#format'], $element['#text_summary_trim_length']);
    return $element;
  }

}
