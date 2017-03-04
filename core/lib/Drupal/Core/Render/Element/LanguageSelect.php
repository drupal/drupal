<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a form element for selecting a language.
 *
 * This does not render an actual form element, but always returns the value of
 * the default language. It is then extended by Language module via
 * language_element_info_alter() to provide a proper language selector.
 *
 * @see language_element_info_alter()
 *
 * @FormElement("language_select")
 */
class LanguageSelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#default_value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
  }

}
