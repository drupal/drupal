<?php

/**
 * @file
 * Definition of Drupal\ajax_forms_test\Callbacks.
 */

namespace Drupal\ajax_forms_test;

/**
 * Simple object for testing methods as Ajax callbacks.
 */
class Callbacks {

  /**
   * Ajax callback triggered by select.
   */
  function selectCallback($form, $form_state) {
    $commands = array();
    $commands[] = ajax_command_html('#ajax_selected_color', $form_state['values']['select']);
    $commands[] = ajax_command_data('#ajax_selected_color', 'form_state_value_select', $form_state['values']['select']);
    return array('#type' => 'ajax', '#commands' => $commands);
  }

  /**
   * Ajax callback triggered by checkbox.
   */
  function checkboxCallback($form, $form_state) {
    $commands = array();
    $commands[] = ajax_command_html('#ajax_checkbox_value', (int) $form_state['values']['checkbox']);
    $commands[] = ajax_command_data('#ajax_checkbox_value', 'form_state_value_select', (int) $form_state['values']['checkbox']);
    return array('#type' => 'ajax', '#commands' => $commands);
  }
}
