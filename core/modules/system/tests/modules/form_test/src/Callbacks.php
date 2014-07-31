<?php

/**
 * @file
 * Definition of Drupal\form_test\Callbacks.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\FormStateInterface;

/**
 * Simple class for testing methods as Form API callbacks.
 */
class Callbacks {

  /**
   * Form element validation handler for 'name' in form_test_validate_form().
   */
  public function validateName(&$element, FormStateInterface $form_state) {
    $triggered = FALSE;
    if ($form_state['values']['name'] == 'element_validate') {
      // Alter the form element.
      $element['#value'] = '#value changed by #element_validate';
      // Alter the submitted value in $form_state.
      form_set_value($element, 'value changed by form_set_value() in #element_validate', $form_state);

      $triggered = TRUE;
    }
    if ($form_state['values']['name'] == 'element_validate_access') {
      $form_state['storage']['form_test_name'] = $form_state['values']['name'];
      // Alter the form element.
      $element['#access'] = FALSE;

      $triggered = TRUE;
    }
    elseif (!empty($form_state['storage']['form_test_name'])) {
      // To simplify this test, just take over the element's value into $form_state.
      form_set_value($element, $form_state['storage']['form_test_name'], $form_state);

      $triggered = TRUE;
    }

    if ($triggered) {
      // Output the element's value from $form_state.
      drupal_set_message(t('@label value: @value', array('@label' => $element['#title'], '@value' => $form_state['values']['name'])));

      // Trigger a form validation error to see our changes.
      form_set_error('', $form_state);
    }
  }

}
