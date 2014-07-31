<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\Field\FieldWidget\TextareaWithSummaryWidget.
 */

namespace Drupal\text\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;
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
    return array(
      'rows' => '9',
      'summary_rows' => '3',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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
  function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $display_summary = $items[$delta]->summary || $this->getFieldSetting('display_summary');
    $element['summary'] = array(
      '#type' => $display_summary ? 'textarea' : 'value',
      '#default_value' => $items[$delta]->summary,
      '#title' => t('Summary'),
      '#rows' => $this->getSetting('summary_rows'),
      '#description' => t('Leave blank to use trimmed value of full text as the summary.'),
      '#attached' => array(
        'library' => array('text/drupal.text'),
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
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    $element = parent::errorElement($element, $violation, $form, $form_state);
    return ($element === FALSE) ? FALSE : $element[$violation->arrayPropertyPath[0]];
  }

}
