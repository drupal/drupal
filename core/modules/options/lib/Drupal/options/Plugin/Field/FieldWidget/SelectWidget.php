<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\Field\FieldWidget\SelectWidget.
 */

namespace Drupal\options\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\options\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "options_select",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_text"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += array(
      '#type' => 'select',
      '#options' => $this->getOptions($items[$delta]),
      '#default_value' => $this->getSelectedOptions($items, $delta),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  static protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = decode_entities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyOption() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return static::OPTIONS_EMPTY_NONE;
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return static::OPTIONS_EMPTY_NONE;
      }
      if (!$this->has_value) {
        return static::OPTIONS_EMPTY_SELECT;
      }
    }
  }

}
