<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestTableSelectFormBase.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for tableselect forms.
 */
abstract class FormTestTableSelectFormBase extends FormBase {

  /**
   * Build a form to test the tableselect element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param $element_properties
   *   An array of element properties for the tableselect element.
   *
   * @return array
   *   A form with a tableselect element and a submit button.
   */
  function tableselectFormBuilder($form, FormStateInterface $form_state, $element_properties) {
    list($header, $options) = _form_test_tableselect_get_data();

    $form['tableselect'] = $element_properties;

    $form['tableselect'] += array(
      '#prefix' => '<div id="tableselect-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => FALSE,
      '#empty' => t('Empty text.'),
      '#ajax' => array(
        'callback' => 'form_test_tableselect_ajax_callback',
        'wrapper' => 'tableselect-wrapper',
      ),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

}
