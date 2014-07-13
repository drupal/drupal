<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestStorageForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;

/**
 * A multistep form for testing the form storage.
 *
 * It uses two steps for editing a virtual "thing". Any changes to it are saved
 * in the form storage and have to be present during any step. By setting the
 * request parameter "cache" the form can be tested with caching enabled, as
 * it would be the case, if the form would contain some #ajax callbacks.
 */
class FormTestStorageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_storage_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    if ($form_state['rebuild']) {
      $form_state['input'] = array();
    }
    // Initialize
    if (empty($form_state['storage'])) {
      if (empty($form_state['input'])) {
        $_SESSION['constructions'] = 0;
      }
      // Put the initial thing into the storage
      $form_state['storage'] = array(
        'thing' => array(
          'title' => 'none',
          'value' => '',
        ),
      );
    }
    // Count how often the form is constructed.
    $_SESSION['constructions']++;
    drupal_set_message("Form constructions: " . $_SESSION['constructions']);

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => 'Title',
      '#default_value' => $form_state['storage']['thing']['title'],
      '#required' => TRUE,
    );
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => 'Value',
      '#default_value' => $form_state['storage']['thing']['value'],
      '#element_validate' => array(array($this, 'elementValidateValueCached')),
    );
    $form['continue_button'] = array(
      '#type' => 'button',
      '#value' => 'Reset',
      // Rebuilds the form without keeping the values.
    );
    $form['continue_submit'] = array(
      '#type' => 'submit',
      '#value' => 'Continue submit',
      '#submit' => array(array($this, 'continueSubmitForm')),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    if (\Drupal::request()->get('cache')) {
      // Manually activate caching, so we can test that the storage keeps working
      // when it's enabled.
      $form_state['cache'] = TRUE;
    }

    return $form;
  }

  /**
   * Form element validation handler for 'value' element.
   *
   * Tests updating of cached form storage during validation.
   */
  public function elementValidateValueCached($element, &$form_state) {
    // If caching is enabled and we receive a certain value, change the storage.
    // This presumes that another submitted form value triggers a validation error
    // elsewhere in the form. Form API should still update the cached form storage
    // though.
    if (\Drupal::request()->get('cache') && $form_state['values']['value'] == 'change_title') {
      $form_state['storage']['thing']['changed'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function continueSubmitForm(array &$form, array &$form_state) {
    $form_state['storage']['thing']['title'] = $form_state['values']['title'];
    $form_state['storage']['thing']['value'] = $form_state['values']['value'];
    $form_state['rebuild'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message("Title: " . String::checkPlain($form_state['values']['title']));
    drupal_set_message("Form constructions: " . $_SESSION['constructions']);
    if (isset($form_state['storage']['thing']['changed'])) {
      drupal_set_message("The thing has been changed.");
    }
    $form_state['redirect_route']['route_name'] = '<front>';
  }

}
