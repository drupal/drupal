<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\Field\FieldWidget\TextareaWidget.
 */

namespace Drupal\text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textarea' widget.
 *
 * @FieldWidget(
 *   id = "text_textarea",
 *   label = @Translation("Text area (multiple rows)"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class TextareaWidget extends StringTextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $element = $main_widget['value'];
    $element['#type'] = 'text_format';
    $element['#format'] = $items[$delta]->format;
    $element['#base_type'] = $main_widget['value']['#type'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    if ($violation->arrayPropertyPath == array('format') && isset($element['format']['#access']) && !$element['format']['#access']) {
      // Ignore validation errors for formats if formats may not be changed,
      // i.e. when existing formats become invalid. See filter_process_format().
      return FALSE;
    }
    return $element;
  }

}
