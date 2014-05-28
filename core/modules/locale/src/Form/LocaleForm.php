<?php
/**
 * @file
 * Contains \Drupal\locale\Form\LocaleForm.
 */

namespace Drupal\locale\Form;

/**
 * Temporary form controller for locale module.
 */
class LocaleForm {

  /**
   * Wraps locale_translation_status_form().
   *
   * @todo Remove locale_translation_status_form().
   */
  public function status() {
    module_load_include('pages.inc', 'locale');
    return \Drupal::formBuilder()->getForm('locale_translation_status_form');
  }

}
