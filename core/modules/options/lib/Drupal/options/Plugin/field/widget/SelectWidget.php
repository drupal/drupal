<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\field\widget\SelectWidget.
 */

namespace Drupal\options\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldInterface;

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
  public function formElement(FieldInterface $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $langcode, $form, $form_state);

    $element += array(
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSelectedOptions($items),
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
    $label = strip_tags($label);
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
