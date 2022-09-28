<?php

namespace Drupal\text\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_textarea_with_summary' widget.
 *
 * @FieldWidget(
 *   id = "text_textarea_with_summary",
 *   label = @Translation("Text area with a summary"),
 *   field_types = {
 *     "text_with_summary"
 *   }
 * )
 */
class TextareaWithSummaryWidget extends TextareaWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'rows' => '9',
      'summary_rows' => '3',
      'placeholder' => '',
      'show_summary' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['summary_rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Summary rows'),
      '#default_value' => $this->getSetting('summary_rows'),
      '#description' => $element['rows']['#description'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['show_summary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show the summary field'),
      '#default_value' => $this->getSetting('show_summary'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Number of summary rows: @rows', ['@rows' => $this->getSetting('summary_rows')]);
    if ($this->getSetting('show_summary')) {
      $summary[] = $this->t('Summary field will always be visible');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $display_summary = $items[$delta]->summary || $this->getFieldSetting('display_summary');
    $required = empty($form['#type']) && $this->getFieldSetting('required_summary');

    $element['summary'] = [
      '#type' => $display_summary ? 'textarea' : 'value',
      '#default_value' => $items[$delta]->summary,
      '#title' => $this->t('Summary'),
      '#rows' => $this->getSetting('summary_rows'),
      '#description' => !$required ? $this->t('Leave blank to use trimmed value of full text as the summary.') : '',
      '#attributes' => ['class' => ['js-text-summary', 'text-summary']],
      '#prefix' => '<div class="js-text-summary-wrapper text-summary-wrapper">',
      '#suffix' => '</div>',
      '#weight' => -10,
      '#required' => $required,
    ];

    if (!$this->getSetting('show_summary') && !$required) {
      $element['summary']['#attached']['library'][] = 'text/drupal.text';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    $element = parent::errorElement($element, $violation, $form, $form_state);
    $property_path_array = explode('.', $violation->getPropertyPath());
    return ($element === FALSE) ? FALSE : $element[$property_path_array[1]];
  }

}
