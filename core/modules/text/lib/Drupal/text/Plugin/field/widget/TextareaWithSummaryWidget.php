<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\widget\TextareaWithSummaryWidget.
 */

namespace Drupal\text\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textarea_with_summary' widget.
 *
 * @Plugin(
 *   id = "text_textarea_with_summary",
 *   module = "text",
 *   label = @Translation("Text area with a summary"),
 *   field_types = {
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "rows" = "9",
 *     "summary_rows" = "3",
 *     "placeholder" = ""
 *   }
 * )
 */
class TextareaWithSummaryWidget extends TextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['summary_rows'] = array(
      '#type' => 'number',
      '#title' => t('Summary rows'),
      '#default_value' => $this->getSetting('summary_rows'),
      '#required' => TRUE,
      '#min' => 1,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = t('Number of summary rows: !rows', array('!rows' => $this->getSetting('summary_rows')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $langcode, $form, $form_state);

    $display_summary = !empty($items[$delta]['summary']) || $this->getFieldSetting('display_summary');
    $element['summary'] = array(
      '#type' => $display_summary ? 'textarea' : 'value',
      '#default_value' => isset($items[$delta]['summary']) ? $items[$delta]['summary'] : NULL,
      '#title' => t('Summary'),
      '#rows' => $this->getSetting('summary_rows'),
      '#description' => t('Leave blank to use trimmed value of full text as the summary.'),
      '#attached' => array(
        'library' => array(array('text', 'drupal.text')),
      ),
      '#attributes' => array('class' => array('text-summary')),
      '#prefix' => '<div class="text-summary-wrapper">',
      '#suffix' => '</div>',
      '#weight' => -10,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, array &$form_state) {
    return $element[$violation->arrayPropertyPath[0]];
  }

}
