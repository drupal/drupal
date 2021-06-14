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
  public function selectCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_selected_color', $form_state->getValue('select')));
    $response->addCommand(new DataCommand('#ajax_selected_color', 'form_state_value_select', $form_state->getValue('select')));
    return $response;
  }

  /**
   * Ajax callback triggered by date.
   */
  public function dateCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $date = $form_state->getValue('date');
    $response->addCommand(new HtmlCommand('#ajax_date_value', sprintf('<div>%s</div>', $date)));
    $response->addCommand(new DataCommand('#ajax_date_value', 'form_state_value_date', $form_state->getValue('date')));
    return $response;
  }

  /**
   * Ajax callback triggered by datetime.
   */
  public function datetimeCallback($form, FormStateInterface $form_state) {
    $datetime = $form_state->getValue('datetime')['date'] . ' ' . $form_state->getValue('datetime')['time'];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_datetime_value', sprintf('<div>%s</div>', $datetime)));
    $response->addCommand(new DataCommand('#ajax_datetime_value', 'form_state_value_datetime', $datetime));
    return $response;
  }

  /**
   * Ajax callback triggered by checkbox.
   */
  public function checkboxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_checkbox_value', $form_state->getValue('checkbox') ? 'checked' : 'unchecked'));
    $response->addCommand(new DataCommand('#ajax_checkbox_value', 'form_state_value_select', (int) $form_state->getValue('checkbox')));
    return $response;
  }

  /**
   * Ajax callback to confirm image button was submitted.
   */
  public function imageButtonCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_image_button_result', "<div id='ajax-1-more-div'>Something witty!</div>"));
    return $response;
  }

  /**
   * Ajax callback triggered by the checkbox in a #group.
   */
  public function checkboxGroupCallback($form, FormStateInterface $form_state) {
    return $form['checkbox_in_group_wrapper'];
  }

}
