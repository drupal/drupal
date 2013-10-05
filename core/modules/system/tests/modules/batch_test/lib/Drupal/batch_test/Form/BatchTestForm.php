<?php

/**
 * @file
 * Contains \Drupal\batch_test\Form\BatchTestForm.
 */

namespace Drupal\batch_test\Form;

/**
 * Temporary form controller for batch_test module.
 */
class BatchTestForm {

  /**
   * @todo Remove batch_test_simple_form().
   */
  public function testForm() {
    return drupal_get_form('batch_test_simple_form');
  }

  /**
   * @todo Remove batch_test_multistep_form().
   */
  public function testMultistepForm() {
    return drupal_get_form('batch_test_multistep_form');
  }

  /**
   * @todo Remove batch_test_chained_form().
   */
  public function testChainedForm() {
    return drupal_get_form('batch_test_chained_form');
  }

}
