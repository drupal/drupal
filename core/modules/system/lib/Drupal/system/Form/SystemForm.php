<?php
/**
 * @file
 * Contains \Drupal\system\Form\SystemForm.
 */

namespace Drupal\system\Form;

/**
 * Temporary form controller for system module.
 */
class SystemForm {

  /**
   * Wraps system_date_format_localize_form().
   *
   * @todo Remove system_date_format_localize_form().
   */
  public function localizeDateFormat($langcode) {
    module_load_include('admin.inc', 'system');
    return drupal_get_form('system_date_format_localize_form', $langcode);
  }

}
