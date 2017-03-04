<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder to test button click detection.
 */
class FormTestClickedButtonForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_clicked_button';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $first = NULL, $second = NULL, $third = NULL) {
    // A single text field. In IE, when a form has only one non-button input field
    // and the ENTER key is pressed while that field has focus, the form is
    // submitted without any information identifying the button responsible for
    // the submission. In other browsers, the form is submitted as though the
    // first button were clicked.
    $form['text'] = [
      '#title' => 'Text',
      '#type' => 'textfield',
    ];

    // Loop through each path argument, adding buttons based on the information
    // in the argument. For example, if the path is
    // form-test/clicked-button/s/i/rb, then 3 buttons are added: a 'submit', an
    // 'image_button', and a 'button' with #access=FALSE. This enables form.test
    // to test a variety of combinations.
    $i = 0;
    $args = [$first, $second, $third];
    foreach ($args as $arg) {
      $name = 'button' . ++$i;
      // 's', 'b', or 'i' in the argument define the button type wanted.
      if (strpos($arg, 's') !== FALSE) {
        $type = 'submit';
      }
      elseif (strpos($arg, 'b') !== FALSE) {
        $type = 'button';
      }
      elseif (strpos($arg, 'i') !== FALSE) {
        $type = 'image_button';
      }
      else {
        $type = NULL;
      }
      if (isset($type)) {
        $form[$name] = [
          '#type' => $type,
          '#name' => $name,
        ];
        // Image buttons need a #src; the others need a #value.
        if ($type == 'image_button') {
          $form[$name]['#src'] = 'core/misc/druplicon.png';
        }
        else {
          $form[$name]['#value'] = $name;
        }
        // 'r' for restricted, so we can test that button click detection code
        // correctly takes #access security into account.
        if (strpos($arg, 'r') !== FALSE) {
          $form[$name]['#access'] = FALSE;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($triggering_element = $form_state->getTriggeringElement()) {
      drupal_set_message(t('The clicked button is %name.', ['%name' => $triggering_element['#name']]));
    }
    else {
      drupal_set_message('There is no clicked button.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Submit handler for form_test_clicked_button executed.');
  }

}
