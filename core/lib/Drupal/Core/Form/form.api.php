<?php

/**
 * @file
 * Callbacks and hooks related to form system.
 */

/**
 * @addtogroup callbacks
 * @{
 */

/**
 * Perform a single batch operation.
 *
 * Callback for batch_set().
 *
 * @param $MULTIPLE_PARAMS
 *   Additional parameters specific to the batch. These are specified in the
 *   array passed to batch_set().
 * @param $context
 *   The batch context array, passed by reference. This contains the following
 *   properties:
 *   - 'finished': A float number between 0 and 1 informing the processing
 *     engine of the completion level for the operation. 1 (or no value
 *     explicitly set) means the operation is finished: the operation will not
 *     be called again, and execution passes to the next operation or the
 *     callback_batch_finished() implementation. Any other value causes this
 *     operation to be called again; however it should be noted that the value
 *     set here does not persist between executions of this callback: each time
 *     it is set to 1 by default by the batch system.
 *   - 'sandbox': This may be used by operations to persist data between
 *     successive calls to the current operation. Any values set in
 *     $context['sandbox'] will be there the next time this function is called
 *     for the current operation. For example, an operation may wish to store a
 *     pointer in a file or an offset for a large query. The 'sandbox' array key
 *     is not initially set when this callback is first called, which makes it
 *     useful for determining whether it is the first call of the callback or
 *     not:
 *     @code
 *       if (empty($context['sandbox'])) {
 *         // Perform set-up steps here.
 *       }
 *     @endcode
 *     The values in the sandbox are stored and updated in the database between
 *     http requests until the batch finishes processing. This avoids problems
 *     if the user navigates away from the page before the batch finishes.
 *   - 'message': A text message displayed in the progress page.
 *   - 'results': The array of results gathered so far by the batch processing.
 *     This array is highly useful for passing data between operations. After
 *     all operations have finished, this is passed to callback_batch_finished()
 *     where results may be referenced to display information to the end-user,
 *     such as how many total items were processed.
 */
function callback_batch_operation($MULTIPLE_PARAMS, &$context) {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['max'] = db_query('SELECT COUNT(DISTINCT nid) FROM {node}')->fetchField();
  }

  // For this example, we decide that we can safely process
  // 5 nodes at a time without a timeout.
  $limit = 5;

  // With each pass through the callback, retrieve the next group of nids.
  $result = db_query_range("SELECT nid FROM {node} WHERE nid > %d ORDER BY nid ASC", $context['sandbox']['current_node'], 0, $limit);
  while ($row = db_fetch_array($result)) {

    // Here we actually perform our processing on the current node.
    $node_storage->resetCache(array($row['nid']));
    $node = $node_storage->load($row['nid']);
    $node->value1 = $options1;
    $node->value2 = $options2;
    node_save($node);

    // Store some result for post-processing in the finished callback.
    $context['results'][] = $node->title;

    // Update our progress information.
    $context['sandbox']['progress']++;
    $context['sandbox']['current_node'] = $node->nid;
    $context['message'] = t('Now processing %node', array('%node' => $node->title));
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Complete a batch process.
 *
 * Callback for batch_set().
 *
 * This callback may be specified in a batch to perform clean-up operations, or
 * to analyze the results of the batch operations.
 *
 * @param $success
 *   A boolean indicating whether the batch has completed successfully.
 * @param $results
 *   The value set in $context['results'] by callback_batch_operation().
 * @param $operations
 *   If $success is FALSE, contains the operations that remained unprocessed.
 */
function callback_batch_finished($success, $results, $operations) {
  if ($success) {
    // Here we do something meaningful with the results.
    $message = t("@count items were processed.", array(
      '@count' => count($results),
      ));
    $list = array(
      '#theme' => 'item_list',
      '#items' => $results,
    );
    $message .= drupal_render($list);
    drupal_set_message($message);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $message = t('An error occurred while processing %error_operation with arguments: @arguments', array(
      '%error_operation' => $error_operation[0],
      '@arguments' => print_r($error_operation[1], TRUE)
    ));
    drupal_set_message($message, 'error');
  }
}

/**
 * @} End of "addtogroup callbacks".
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the Ajax command data that is sent to the client.
 *
 * @param \Drupal\Core\Ajax\CommandInterface[] $data
 *   An array of all the rendered commands that will be sent to the client.
 */
function hook_ajax_render_alter(array &$data) {
  // Inject any new status messages into the content area.
  $status_messages = array('#type' => 'status_messages');
  $command = new \Drupal\Core\Ajax\PrependCommand('#block-system-main .content', \Drupal::service('renderer')->renderRoot($status_messages));
  $data[] = $command->render();
}

/**
 * Perform alterations before a form is rendered.
 *
 * One popular use of this hook is to add form elements to the node form. When
 * altering a node form, the node entity can be retrieved by invoking
 * $form_state->getFormObject()->getEntity().
 *
 * Implementations are responsible for adding cache contexts/tags/max-age as
 * needed. See https://www.drupal.org/developing/api/8/cache.
 *
 * In addition to hook_form_alter(), which is called for all forms, there are
 * two more specific form hooks available. The first,
 * hook_form_BASE_FORM_ID_alter(), allows targeting of a form/forms via a base
 * form (if one exists). The second, hook_form_FORM_ID_alter(), can be used to
 * target a specific form directly.
 *
 * The call order is as follows: all existing form alter functions are called
 * for module A, then all for module B, etc., followed by all for any base
 * theme(s), and finally for the theme itself. The module order is determined
 * by system weight, then by module name.
 *
 * Within each module, form alter hooks are called in the following order:
 * first, hook_form_alter(); second, hook_form_BASE_FORM_ID_alter(); third,
 * hook_form_FORM_ID_alter(). So, for each module, the more general hooks are
 * called first followed by the more specific.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 *
 * @ingroup form_api
 */
function hook_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if (isset($form['type']) && $form['type']['#value'] . '_node_settings' == $form_id) {
    $upload_enabled_types = \Drupal::config('mymodule.settings')->get('upload_enabled_types');
    $form['workflow']['upload_' . $form['type']['#value']] = array(
      '#type' => 'radios',
      '#title' => t('Attachments'),
      '#default_value' => in_array($form['type']['#value'], $upload_enabled_types) ? 1 : 0,
      '#options' => array(t('Disabled'), t('Enabled')),
    );
    // Add a custom submit handler to save the array of types back to the config file.
    $form['actions']['submit']['#submit'][] = 'mymodule_upload_enabled_types_submit';
  }
}

/**
 * Provide a form-specific alteration instead of the global hook_form_alter().
 *
 * Implementations are responsible for adding cache contexts/tags/max-age as
 * needed. See https://www.drupal.org/developing/api/8/cache.
 *
 * Modules can implement hook_form_FORM_ID_alter() to modify a specific form,
 * rather than implementing hook_form_alter() and checking the form ID, or
 * using long switch statements to alter multiple forms.
 *
 * Form alter hooks are called in the following order: hook_form_alter(),
 * hook_form_BASE_FORM_ID_alter(), hook_form_FORM_ID_alter(). See
 * hook_form_alter() for more details.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_alter()
 * @see hook_form_BASE_FORM_ID_alter()
 * @see \Drupal\Core\Form\FormBuilderInterface::prepareForm()
 *
 * @ingroup form_api
 */
function hook_form_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register_form" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Provide a form-specific alteration for shared ('base') forms.
 *
 * Implementations are responsible for adding cache contexts/tags/max-age as
 * needed. See https://www.drupal.org/developing/api/8/cache.
 *
 * By default, when \Drupal::formBuilder()->getForm() is called, Drupal looks
 * for a function with the same name as the form ID, and uses that function to
 * build the form. In contrast, base forms allow multiple form IDs to be mapped
 * to a single base (also called 'factory') form function.
 *
 * Modules can implement hook_form_BASE_FORM_ID_alter() to modify a specific
 * base form, rather than implementing hook_form_alter() and checking for
 * conditions that would identify the shared form constructor.
 *
 * To identify the base form ID for a particular form (or to determine whether
 * one exists) check the $form_state. The base form ID is stored under
 * $form_state->getBuildInfo()['base_form_id'].
 *
 * Form alter hooks are called in the following order: hook_form_alter(),
 * hook_form_BASE_FORM_ID_alter(), hook_form_FORM_ID_alter(). See
 * hook_form_alter() for more details.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form.
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_alter()
 * @see hook_form_FORM_ID_alter()
 * @see \Drupal\Core\Form\FormBuilderInterface::prepareForm()
 *
 * @ingroup form_api
 */
function hook_form_BASE_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Modification for the form with the given BASE_FORM_ID goes here. For
  // example, if BASE_FORM_ID is "node_form", this code would run on every
  // node form, regardless of node type.

  // Add a checkbox to the node form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Alter batch information before a batch is processed.
 *
 * Called by batch_process() to allow modules to alter a batch before it is
 * processed.
 *
 * @param $batch
 *   The associative array of batch information. See batch_set() for details on
 *   what this could contain.
 *
 * @see batch_set()
 * @see batch_process()
 *
 * @ingroup batch
 */
function hook_batch_alter(&$batch) {
}

/**
 * @} End of "addtogroup hooks".
 */
