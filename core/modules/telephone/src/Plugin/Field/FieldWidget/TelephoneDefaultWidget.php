<?php

namespace Drupal\telephone\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'telephone_default' widget.
 *
 * @FieldWidget(
 *   id = "telephone_default",
 *   label = @Translation("Telephone number"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class TelephoneDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
    ];
    return $element;
  }

}
