<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\Field\FieldWidget\ButtonsWidget.
 */

namespace Drupal\options\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "options_buttons",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "boolean",
 *     "list_integer",
 *     "list_float",
 *     "list_text",
 *   },
 *   multiple_values = TRUE
 * )
 */
class ButtonsWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items[$delta]);
    $selected = $this->getSelectedOptions($items);

    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = array(key($options));
    }

    if ($this->multiple) {
      $element += array(
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $options,
      );
    }
    else {
      $element += array(
        '#type' => 'radios',
        // Radio buttons need a scalar value. Take the first default value, or
        // default to NULL so that the form element is properly recognized as
        // not having a default value.
        '#default_value' => $selected ? reset($selected) : NULL,
        '#options' => $options,
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyOption() {
    if (!$this->required && !$this->multiple) {
      return static::OPTIONS_EMPTY_NONE;
    }
  }

}
