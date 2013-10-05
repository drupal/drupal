<?php

/**
 * @file
 * Contains \Drupal\ajax_forms_test\Form\AjaxFormsTestForm.
 */

namespace Drupal\ajax_forms_test\Form;

/**
 * Temporary form controller for ajax_forms_test module.
 */
class AjaxFormsTestForm {

  /**
   * @todo Remove ajax_forms_test_simple_form().
   */
  public function getForm() {
    return drupal_get_form('ajax_forms_test_simple_form');
  }

  /**
   * @todo Remove ajax_forms_test_ajax_commands_form().
   */
  public function commandsForm() {
    return drupal_get_form('ajax_forms_test_ajax_commands_form');
  }

  /**
   * @todo Remove ajax_forms_test_validation_form().
   */
  public function validationForm() {
    return drupal_get_form('ajax_forms_test_validation_form');
  }

  /**
   * @todo Remove ajax_forms_test_lazy_load_form().
   */
  public function lazyLoadForm() {
    return drupal_get_form('ajax_forms_test_lazy_load_form');
  }

}
