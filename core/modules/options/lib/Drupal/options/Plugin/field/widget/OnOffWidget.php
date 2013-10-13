<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\field\widget\OnOffWidget.
 */

namespace Drupal\options\Plugin\field\widget;

use Drupal\Core\Entity\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'options_onoff' widget.
 *
 * @FieldWidget(
 *   id = "options_onoff",
 *   label = @Translation("Single on/off checkbox"),
 *   field_types = {
 *     "list_boolean"
 *   },
 *   settings = {
 *     "display_label" = FALSE,
 *   },
 *   multiple_values = TRUE
 * )
 */
class OnOffWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['display_label'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use field label instead of the "On value" as label'),
      '#default_value' => $this->getSetting('display_label'),
      '#weight' => -1,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $display_label = $this->getSetting('display_label');
    $summary[] = t('Use field label: @display_label', array('@display_label' => ($display_label ? t('Yes') : 'No')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items[$delta]);
    $selected = $this->getSelectedOptions($items);

    $element += array(
      '#type' => 'checkbox',
      '#default_value' => !empty($selected[0]),
    );

    // Override the title from the incoming $element.
    if ($this->getSetting('display_label')) {
      $element['#title'] = $this->fieldDefinition->getFieldLabel();
    }
    else {
      $element['#title'] = isset($options[1]) ? $options[1] : '';
    }

    return $element;
  }

}
