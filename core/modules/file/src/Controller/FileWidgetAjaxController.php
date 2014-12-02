<?php

/**
 * @file
 * Contains \Drupal\file\FileWidgetAjaxController.
 */

namespace Drupal\file\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\system\Controller\FormAjaxController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Defines a controller to respond to file widget AJAX requests.
 */
class FileWidgetAjaxController extends FormAjaxController {

  /**
   * Processes AJAX file uploads and deletions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AjaxResponse object.
   */
  public function upload(Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));
    $form_build_id = $request->query->get('form_build_id');
    $request_form_build_id = $request->request->get('form_build_id');

    if (empty($request_form_build_id) || $form_build_id !== $request_form_build_id) {
      // Invalid request.
      drupal_set_message(t('An unrecoverable error occurred. The uploaded file likely exceeded the maximum file size (@size) that this server supports.', array('@size' => format_size(file_upload_max_size()))), 'error');
      $response = new AjaxResponse();
      $status_messages = array('#theme' => 'status_messages');
      return $response->addCommand(new ReplaceCommand(NULL, drupal_render($status_messages)));
    }

    try {
      /** @var $ajaxForm \Drupal\system\FileAjaxForm */
      $ajaxForm = $this->getForm($request);
      $form = $ajaxForm->getForm();
      $form_state = $ajaxForm->getFormState();
      $commands = $ajaxForm->getCommands();
    }
    catch (HttpExceptionInterface $e) {
      // Invalid form_build_id.
      drupal_set_message(t('An unrecoverable error occurred. Use of this form has expired. Try reloading the page and submitting again.'), 'error');
      $response = new AjaxResponse();
      $status_messages = array('#theme' => 'status_messages');
      return $response->addCommand(new ReplaceCommand(NULL, drupal_render($status_messages)));
    }

    // Get the current element and count the number of files.
    $current_element = NestedArray::getValue($form, $form_parents);
    $current_file_count = isset($current_element['#file_upload_delta']) ? $current_element['#file_upload_delta'] : 0;

    // Process user input. $form and $form_state are modified in the process.
    $this->formBuilder->processForm($form['#form_id'], $form, $form_state);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    // Add the special Ajax class if a new file was added.
    if (isset($form['#file_upload_delta']) && $current_file_count < $form['#file_upload_delta']) {
      $form[$current_file_count]['#attributes']['class'][] = 'ajax-new-content';
    }
    // Otherwise just add the new content class on a placeholder.
    else {
      $form['#suffix'] .= '<span class="ajax-new-content"></span>';
    }

    $status_messages = array('#theme' => 'status_messages');
    $form['#prefix'] .= drupal_render($status_messages);
    $output = drupal_render($form);
    drupal_process_attached($form);
    $js = _drupal_add_js();
    $settings = drupal_merge_js_settings($js['settings']['data']);

    $response = new AjaxResponse();
    foreach ($commands as $command) {
      $response->addCommand($command, TRUE);
    }
    return $response->addCommand(new ReplaceCommand(NULL, $output, $settings));
  }

  /**
   * Returns the progress status for a file upload process.
   *
   * @param string $key
   *   The unique key for this upload process.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object.
   */
  public function progress($key) {
    $progress = array(
      'message' => t('Starting upload...'),
      'percentage' => -1,
    );

    $implementation = file_progress_implementation();
    if ($implementation == 'uploadprogress') {
      $status = uploadprogress_get_info($key);
      if (isset($status['bytes_uploaded']) && !empty($status['bytes_total'])) {
        $progress['message'] = t('Uploading... (@current of @total)', array('@current' => format_size($status['bytes_uploaded']), '@total' => format_size($status['bytes_total'])));
        $progress['percentage'] = round(100 * $status['bytes_uploaded'] / $status['bytes_total']);
      }
    }
    elseif ($implementation == 'apc') {
      $status = apc_fetch('upload_' . $key);
      if (isset($status['current']) && !empty($status['total'])) {
        $progress['message'] = t('Uploading... (@current of @total)', array('@current' => format_size($status['current']), '@total' => format_size($status['total'])));
        $progress['percentage'] = round(100 * $status['current'] / $status['total']);
      }
    }

    return new JsonResponse($progress);
  }

}
