<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\widget\TextareaWithSummaryWidget.
 */

namespace Drupal\text\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

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
  public function errorElement(array $element, array $error, array $form, array &$form_state) {
    switch ($error['error']) {
      case 'text_summary_max_length':
        $error_element = $element['summary'];
        break;

      default:
        $error_element = $element;
        break;
    }

    return $error_element;
  }

}
