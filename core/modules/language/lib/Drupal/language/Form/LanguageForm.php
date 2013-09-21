<?php
/**
 * @file
 * Contains \Drupal\language\Form\LanguageForm.
 */

namespace Drupal\language\Form;

/**
 * Temporary form controller for language module.
 */
class LanguageForm {

  /**
   * Wraps language_negotiation_configure_form().
   *
   * @todo Remove language_negotiation_configure_form().
   */
  public function negotiation() {
    module_load_include('admin.inc', 'language');
    return drupal_get_form('language_negotiation_configure_form');
  }

}
