<?php

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an item list class for text fields.
 */
class TextFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    if ($allowed_formats = $this->getSetting('allowed_formats')) {
      $field_name = $this->definition->getName();
      $submitted_values = $form_state->getValue([
        'default_value_input',
        $field_name,
      ]);
      foreach ($submitted_values as $delta => $value) {
        if (!in_array($value['format'], $allowed_formats, TRUE)) {
          $form_state->setErrorByName(
            "default_value_input][{$field_name}][{$delta}][format",
            $this->t("The selected text format is not allowed.")
          );
        }
      }
    }
    parent::defaultValuesFormValidate($element, $form, $form_state);
  }

}
