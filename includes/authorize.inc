<?php
// $Id: authorize.inc,v 1.9 2010/01/30 07:59:24 dries Exp $

/**
 * @file
 * Helper functions and form handlers used for the authorize.php script.
 */

/**
 * Build the form for choosing a FileTransfer type and supplying credentials.
 */
function authorize_filetransfer_form($form_state) {
  global $base_url, $is_https;
  $form = array();

  // If possible, we want to post this form securely via https.
  $form['#https'] = TRUE;

  // CSS we depend on lives in modules/system/maintenance.css, which is loaded
  // via the default maintenance theme.
  $form['#attached']['js'][] = $base_url . '/misc/authorize.js';

  // Get all the available ways to transfer files.
  if (empty($_SESSION['authorize_filetransfer_backends'])) {
    drupal_set_message(t('Unable to continue, no available methods of file transfer'), 'error');
    return array();
  }
  $available_backends = $_SESSION['authorize_filetransfer_backends'];
  uasort($available_backends, 'drupal_sort_weight');

  if (!$is_https) {
    drupal_set_message(t('WARNING: You are not using an encrypted connection, so your password will be sent in plain text. <a href="@https-link">Learn more</a>.', array('@https-link' => 'http://drupal.org/https-information')), 'error');
  }

  // Decide on a default backend.
  if (isset($form_state['values']['connection_settings']['authorize_filetransfer_default'])) {
    $authorize_filetransfer_default = $form_state['values']['connection_settings']['authorize_filetransfer_default'];
  }
  elseif ($authorize_filetransfer_default = variable_get('authorize_filetransfer_default', NULL));
  else {
    $authorize_filetransfer_default = key($available_backends);
  }

  $form['information']['main_header'] = array(
    '#prefix' => '<h3>',
    '#markup' => t('To continue, provide your server connection details'),
    '#suffix' => '</h3>',
  );

  $form['connection_settings']['#tree'] = TRUE;
  $form['connection_settings']['authorize_filetransfer_default'] = array(
    '#type' => 'select',
    '#title' => t('Connection method'),
    '#default_value' => $authorize_filetransfer_default,
    '#weight' => -10,
  );

  /*
   * Here we create two submit buttons. For a JS enabled client, they will
   * only ever see submit_process. However, if a client doesn't have JS
   * enabled, they will see submit_connection on the first form (when picking
   * what filetransfer type to use, and submit_process on the second one (which
   * leads to the actual operation).
   */
  $form['submit_connection'] = array(
    '#prefix' => "<br style='clear:both'/>",
    '#name' => 'enter_connection_settings',
    '#type' => 'submit',
    '#value' => t('Enter connection settings'),
    '#weight' => 100,
  );

  $form['submit_process'] = array(
    '#name' => 'process_updates',
    '#type' => 'submit',
    '#value' => t('Continue'),
    '#weight' => 100,
    '#attributes' => array('style' => 'display:none'),
  );

  // Build a hidden fieldset for each one.
  foreach ($available_backends as $name => $backend) {
    $form['connection_settings']['authorize_filetransfer_default']['#options'][$name] = $backend['title'];
    $form['connection_settings'][$name] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => "filetransfer-$name filetransfer"),
      '#title' => t('@backend connection settings', array('@backend' => $backend['title'])),
    );

    $current_settings = variable_get('authorize_filetransfer_connection_settings_' . $name, array());
    $form['connection_settings'][$name] += system_get_filetransfer_settings_form($name, $current_settings);

    // Start non-JS code.
    if (isset($form_state['values']['connection_settings']['authorize_filetransfer_default']) && $form_state['values']['connection_settings']['authorize_filetransfer_default'] == $name) {

      // If the user switches from JS to non-JS, Drupal (and Batch API) will
      // barf. This is a known bug: http://drupal.org/node/229825.
      setcookie('has_js', '', time() - 3600, '/');
      unset($_COOKIE['has_js']);

      // Change the submit button to the submit_process one.
      $form['submit_process']['#attributes'] = array();
      unset($form['submit_connection']);

      // Activate the proper filetransfer settings form.
      $form['connection_settings'][$name]['#attributes']['style'] = 'display:block';
      // Disable the select box.
      $form['connection_settings']['authorize_filetransfer_default']['#disabled'] = TRUE;

      // Create a button for changing the type of connection.
      $form['connection_settings']['change_connection_type'] = array(
        '#name' => 'change_connection_type',
        '#type' => 'submit',
        '#value' => t('Change connection type'),
        '#weight' => -5,
        '#attributes' => array('class' => 'filetransfer-change-connection-type'),
      );
    }
    // End non-JS code.
  }
  return $form;
}

/**
 * Validate callback for the filetransfer authorization form.
 *
 * @see authorize_filetransfer_form()
 */
function authorize_filetransfer_form_validate($form, &$form_state) {
  if (isset($form_state['values']['connection_settings'])) {
    $backend = $form_state['values']['connection_settings']['authorize_filetransfer_default'];
    $filetransfer = authorize_get_filetransfer($backend, $form_state['values']['connection_settings'][$backend]);
    try {
      if (!$filetransfer) {
        throw new Exception(t('Error, this type of connection protocol (%backend) does not exist.', array('%backend' => $backend)));
      }
      $filetransfer->connect();
    }
    catch (Exception $e) {
      form_set_error('connection_settings', $e->getMessage());
    }
  }
}

/**
 * Submit callback when a file transfer is being authorized.
 *
 * @see authorize_filetransfer_form()
 */
function authorize_filetransfer_form_submit($form, &$form_state) {
  global $base_url;
  switch ($form_state['clicked_button']['#name']) {
    case 'process_updates':

      // Save the connection settings to the DB.
      $filetransfer_backend = $form_state['values']['connection_settings']['authorize_filetransfer_default'];

      // If the database is available then try to save our settings. We have
      // to make sure it is available since this code could potentially (will
      // likely) be called during the installation process, before the
      // database is set up.
      if (db_is_active()) {
        $connection_settings = array();
        foreach ($form_state['values']['connection_settings'][$filetransfer_backend] as $key => $value) {
          // We do *not* want to store passwords in the database, unless the
          // backend explicitly says so via the magic #filetransfer_save form
          // property. Otherwise, we store everything that's not explicitly
          // marked with #filetransfer_save set to FALSE.
          if (!isset($form['connection_settings'][$filetransfer_backend][$key]['#filetransfer_save'])) {
            if ($form['connection_settings'][$filetransfer_backend][$key]['#type'] != 'password') {
              $connection_settings[$key] = $value;
            }
          }
          // The attribute is defined, so only save if set to TRUE.
          elseif ($form['connection_settings'][$filetransfer_backend][$key]['#filetransfer_save']) {
            $connection_settings[$key] = $value;
          }
        }
        // Set this one as the default authorize method.
        variable_set('authorize_filetransfer_default', $filetransfer_backend);
        // Save the connection settings minus the password.
        variable_set('authorize_filetransfer_connection_settings_' . $filetransfer_backend, $connection_settings);

        $filetransfer = authorize_get_filetransfer($filetransfer_backend, $form_state['values']['connection_settings'][$filetransfer_backend]);

        // Now run the operation.
        authorize_run_operation($filetransfer);
      }
      break;

    case 'enter_connection_settings':
      $form_state['rebuild'] = TRUE;
      break;

    case 'change_connection_type':
      $form_state['rebuild'] = TRUE;
      unset($form_state['values']['connection_settings']['authorize_filetransfer_default']);
      break;
  }
}

/**
 * Run the operation specified in $_SESSION['authorize_operation']
 *
 * @param $filetransfer
 *   The FileTransfer object to use for running the operation.
 */
function authorize_run_operation($filetransfer) {
  $operation = $_SESSION['authorize_operation'];
  unset($_SESSION['authorize_operation']);

  if (!empty($operation['page_title'])) {
    drupal_set_title(check_plain($operation['page_title']));
  }

  require_once DRUPAL_ROOT . '/' . $operation['file'];
  call_user_func_array($operation['callback'], array_merge(array($filetransfer), $operation['arguments']));
}

/**
 * Get a FileTransfer class for a specific transfer method and settings.
 *
 * @param $backend
 *   The FileTransfer backend to get the class for.
 * @param $settings
 *   Array of settings for the FileTransfer.
 * @return
 *   An instantiated FileTransfer object for the requested method and settings,
 *   or FALSE if there was an error finding or instantiating it.
 */
function authorize_get_filetransfer($backend, $settings = array()) {
  $filetransfer = FALSE;
  if (!empty($_SESSION['authorize_filetransfer_backends'][$backend])) {
    $filetransfer = call_user_func_array(array($_SESSION['authorize_filetransfer_backends'][$backend]['class'], 'factory'), array(DRUPAL_ROOT, $settings));
  }
  return $filetransfer;
}

