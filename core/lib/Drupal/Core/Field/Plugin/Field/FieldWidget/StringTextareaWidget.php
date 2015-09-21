<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'string_textarea' widget.
 *
 * @FieldWidget(
 *   id = "string_textarea",
 *   label = @Translation("Text area (multiple rows)"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class StringTextareaWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'rows' => '5',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['rows'] = array(
      '#type' => 'number',
      '#title' => t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Number of rows: @rows', array('@rows' => $this->getSetting('rows')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'textarea',
      '#default_value' => $items[$delta]->value,
      '#rows' => $this->getSetting('rows'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#attributes' => array('class' => array('js-text-full', 'text-full')),
    );

    return $element;
  }

}
