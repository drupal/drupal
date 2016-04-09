<?php

namespace Drupal\ajax_forms_test;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple object for testing methods as Ajax callbacks.
 */
class Callbacks {

  /**
   * Ajax callback triggered by select.
   */
  function selectCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_selected_color', $form_state->getValue('select')));
    $response->addCommand(new DataCommand('#ajax_selected_color', 'form_state_value_select', $form_state->getValue('select')));
    return $response;
  }

  /**
   * Ajax callback triggered by checkbox.
   */
  function checkboxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_checkbox_value', (int) $form_state->getValue('checkbox')));
    $response->addCommand(new DataCommand('#ajax_checkbox_value', 'form_state_value_select', (int) $form_state->getValue('checkbox')));
    return $response;
  }

  /**
   * Ajax callback triggered by the checkbox in a #group.
   */
  function checkboxGroupCallback($form, FormStateInterface $form_state) {
    return $form['checkbox_in_group_wrapper'];
  }

}
