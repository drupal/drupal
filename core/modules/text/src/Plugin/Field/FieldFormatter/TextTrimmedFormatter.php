<?php

namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\text\TextSummary;

/**
 * Plugin implementation of the 'text_trimmed' formatter.
 */
#[FieldFormatter(
  id: 'text_trimmed',
  label: new TranslatableMarkup('Trimmed'),
  field_types: [
    'text',
    'text_long',
  ],
)]
class TextTrimmedFormatter extends FormatterBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => '600',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['trim_length'] = [
      '#title' => $this->t('Trimmed limit'),
      '#type' => 'number',
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#description' => $this->t('If the summary is not set, the trimmed %label field will end at the last full sentence before this character limit.', ['%label' => $this->fieldDefinition->getLabel()]),
      '#min' => 1,
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Trimmed limit: @trim_length characters', ['@trim_length' => $this->getSetting('trim_length')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      ];
      $this->addTrimPreRender($elements[$delta]);
    }

    return $elements;
  }

  /**
   * Attaches the trim pre-render callback and trim length to an element.
   *
   * Subclasses that decide per item whether to trim can call this on the
   * fallback branch.
   *
   * @param array $element
   *   A processed_text render element to trim.
   */
  protected function addTrimPreRender(array &$element): void {
    // Make sure any default #pre_render callbacks are set on the element,
    // because text_pre_render_summary() must run last.
    $element += \Drupal::service('element_info')->getInfo($element['#type']);
    $element['#pre_render'][] = [static::class, 'preRenderSummary'];
    // Pass on the trim length to the #pre_render callback via a property.
    $element['#text_summary_trim_length'] = $this->getSetting('trim_length');
  }

  /**
   * Pre-render callback: Renders a processed text element's #markup summary.
   *
   * @param array $element
   *   A structured array with the following key-value pairs:
   *   - #markup: the filtered text (as filtered by filter_pre_render_text())
   *   - #format: containing the machine name of the filter format to be used to
   *     filter the text. Defaults to the fallback format. See
   *     filter_fallback_format().
   *   - #text_summary_trim_length: the desired character length of the summary
   *     (used by \Drupal\text\TextSummary::generate())
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup' trimmed.
   *
   * @see filter_pre_render_text()
   * @see \Drupal\text\TextSummary::generate()
   */
  public static function preRenderSummary(array $element) {
    $element['#markup'] = \Drupal::service(TextSummary::class)->generate($element['#markup'], $element['#format'], $element['#text_summary_trim_length']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderSummary'];
  }

}
