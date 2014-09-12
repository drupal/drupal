<?php

/**
 * @file
 * Handles the server side AJAX interactions of Views.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Ajax\HighlightCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Wrapper around \Drupal\Core\Form\FormBuilderInterface::buildForm() to handle
 * some AJAX stuff automatically.
 * This makes some assumptions about the client.
 */
function views_ajax_form_wrapper($form_class, FormStateInterface &$form_state) {
  // This won't override settings already in.
  if (!$form_state->has('rerender')) {
    $form_state->set('rerender', FALSE);
  }
  $ajax = $form_state->get('ajax');
  // Do not overwrite if the redirect has been disabled.
  if (!$form_state->isRedirectDisabled()) {
    $form_state->disableRedirect($ajax);
  }
  $form_state->disableCache();

  $form = \Drupal::formBuilder()->buildForm($form_class, $form_state);
  $output = drupal_render($form);
  drupal_process_attached($form);

  // These forms have the title built in, so set the title here:
  $title = $form_state->get('title') ?: '';
  if (!$ajax && $title) {
    $form['#attached']['css'][] = drupal_get_path('module', 'views_ui') . '/css/views_ui.admin.css';
  }

  if ($ajax && (!$form_state->isExecuted() || $form_state->get('rerender'))) {
    // If the form didn't execute and we're using ajax, build up a
    // Ajax command list to execute.
    $response = new AjaxResponse();

    $display = '';
    $status_messages = array('#theme' => 'status_messages');
    if ($messages = drupal_render($status_messages)) {
      $display = '<div class="views-messages">' . $messages . '</div>';
    }
    $display .= $output;

    $options = array(
      'dialogClass' => 'views-ui-dialog',
      'width' => '50%',
    );

    $response->addCommand(new OpenModalDialogCommand($title, $display, $options));

    if ($section = $form_state->get('#section')) {
      $response->addCommand(new HighlightCommand('.' . Html::cleanCssIdentifier($section)));
    }

    return $response;
  }

  return $title ? ['#title' => $title, '#markup' => $output] : $output;
}
