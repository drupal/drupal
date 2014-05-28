<?php

/**
 * @file
 * Contains \Drupal\language_elements_test\Form\LanguageElementsTestForm.
 */

namespace Drupal\language_elements_test\Form;

/**
 * Controller routines for language_elements_test routes.
 */
class LanguageElementsTestForm {

  /**
   * Wraps language_elements_configuration_element().
   *
   * @todo Remove language_elements_configuration_element().
   */
  public function configFormElement() {
    return \Drupal::formBuilder()->getForm('language_elements_configuration_element');
  }

  /**
   * Wraps language_element_tests_configuration_element_test().
   *
   * @todo Remove language_element_tests_configuration_element_test().
   */
  public function configFormElementTest() {
    return \Drupal::formBuilder()->getForm('language_elements_configuration_element_test');
  }

}

