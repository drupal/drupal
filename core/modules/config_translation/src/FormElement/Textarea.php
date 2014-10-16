<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\Textarea.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines the textarea element for the configuration translation interface.
 */
class Textarea implements ElementInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormElement(DataDefinitionInterface $definition, LanguageInterface $language, $value) {
    // Estimate a comfortable size of the input textarea.
    $rows_words = ceil(str_word_count($value) / 5);
    $rows_newlines = substr_count($value, "\n" ) + 1;
    $rows = max($rows_words, $rows_newlines);

    return array(
      '#type' => 'textarea',
      '#default_value' => $value,
      '#title' => $this->t($definition->getLabel()) . '<span class="visually-hidden"> (' . $language->getName() . ')</span>',
      '#rows' => $rows,
      '#attributes' => array('lang' => $language->getId()),
    );
  }

}
