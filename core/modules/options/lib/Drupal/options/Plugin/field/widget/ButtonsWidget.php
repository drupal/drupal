<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\field\widget\ButtonsWidget.
 */

namespace Drupal\options\Plugin\field\widget;

use Drupal\Core\Entity\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "options_buttons",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_text",
 *     "list_boolean"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ButtonsWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
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
