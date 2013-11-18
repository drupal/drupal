<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\Textarea.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\Language;

/**
 * Defines the textarea element for the configuration translation interface.
 */
class Textarea extends Element {

  /**
   * {@inheritdoc}
   */
  public function getFormElement(array $definition, Language $language, $value) {
    // Estimate a comfortable size of the input textarea.
    $rows_words = ceil(str_word_count($value) / 5);
    $rows_newlines = substr_count($value, "\n" ) + 1;
    $rows = max($rows_words, $rows_newlines);

    return array(
      '#type' => 'textarea',
      '#default_value' => $value,
      '#title' => $this->t($definition['label']) . '<span class="visually-hidden"> (' . $language->name . ')</span>',
      '#rows' => $rows,
      '#attributes' => array('lang' => $language->id),
    );
  }

}
