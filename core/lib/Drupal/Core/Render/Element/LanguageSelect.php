<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\LanguageSelect.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a form element for selecting a language.
 *
 * @see \Drupal\Core\Render\Element\Select
 *
 * @FormElement("language_select")
 */
class LanguageSelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#input' => TRUE,
      '#default_value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );
  }

}
