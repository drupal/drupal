<?php

namespace Drupal\Core\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'boolean_checkbox' widget.
 *
 * @FieldWidget(
 *   id = "boolean_checkbox",
 *   label = @Translation("Single on/off checkbox"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = TRUE
 * )
 */
class BooleanCheckboxWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_label' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['display_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use field label instead of the "On" label as the label.'),
      '#default_value' => $this->getSetting('display_label'),
      '#weight' => -1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $display_label = $this->getSetting('display_label');
    $summary[] = $this->t('Use field label: @display_label', ['@display_label' => ($display_label ? $this->t('Yes') : $this->t('No'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'checkbox',
      '#default_value' => !empty($items[0]->value),
    ];

    // Override the title from the incoming $element.
    if ($this->getSetting('display_label')) {
      $element['value']['#title'] = $this->fieldDefinition->getLabel();
    }
    else {
      $element['value']['#title'] = $this->fieldDefinition->getSetting('on_label');
    }

    return $element;
  }

}
