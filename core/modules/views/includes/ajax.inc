<?php

/**
 * @file
 * Handles the server side AJAX interactions of Views.
 */

use Drupal\views\Ajax\HighlightCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Wrapper around drupal_build_form to handle some AJAX stuff automatically.
 * This makes some assumptions about the client.
 */
function views_ajax_form_wrapper($form_id, &$form_state) {
  // This won't override settings already in.
  $form_state += array(
    'rerender' => FALSE,
    'no_redirect' => !empty($form_state['ajax']),
    'no_cache' => TRUE,
    'build_info' => array(
      'args' => array(),
    ),
  );

  $form = drupal_build_form($form_id, $form_state);
  $output = drupal_render($form);

  // These forms have the title built in, so set the title here:
  if (empty($form_state['ajax']) && !empty($form_state['title'])) {
    $form['#attached']['css'][] = drupal_get_path('module', 'views_ui') . '/css/views_ui.admin.css';
  }

  if (!empty($form_state['ajax']) && (empty($form_state['executed']) || !empty($form_state['rerender']))) {
    // If the form didn't execute and we're using ajax, build up a
    // Ajax command list to execute.
    $response = new AjaxResponse();

    $display = '';
    $status_messages = array('#theme' => 'status_messages');
    if ($messages = drupal_render($status_messages)) {
      $display = '<div class="views-messages">' . $messages . '</div>';
    }
    $display .= $output;

    $title = empty($form_state['title']) ? '' : $form_state['title'];
    $options = array(
      'dialogClass' => 'views-ui-dialog',
      'width' => '50%',
    );

    $response->addCommand(new OpenModalDialogCommand($title, $display, $options));

    if (!empty($form_state['#section'])) {
      $response->addCommand(new HighlightCommand('.' . drupal_clean_css_identifier($form_state['#section'])));
    }

    return $response;
  }

  return (!empty($form_state['title'])) ? array('#title' => $form_state['title'], '#markup' => $output) : $output;
}
