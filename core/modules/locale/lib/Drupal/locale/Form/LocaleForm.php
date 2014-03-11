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
   * Wraps locale_translate_import_form().
   *
   * @todo Remove locale_translate_import_form().
   */
  public function import() {
    module_load_include('bulk.inc', 'locale');
    return drupal_get_form('locale_translate_import_form');
  }

  /**
   * Wraps locale_translation_status_form().
   *
   * @todo Remove locale_translation_status_form().
   */
  public function status() {
    module_load_include('pages.inc', 'locale');
    return drupal_get_form('locale_translation_status_form');
  }

}
