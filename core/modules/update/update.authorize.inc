<?php

/**
 * @file
 * Callbacks and related functions invoked by authorize.php to update projects.
 *
 * We use the Batch API to actually update each individual project on the site.
 * All of the code in this file is run at a low bootstrap level (modules are not
 * loaded), so these functions cannot assume access to the rest of the code of
 * the Update Manager module.
 */

use Drupal\Core\Updater\UpdaterException;
use Drupal\Core\Url;

/**
 * Updates existing projects when invoked by authorize.php.
 *
 * Callback for system_authorized_init() in
 * update_manager_update_ready_form_submit().
 *
 * @param $filetransfer
 *   The FileTransfer object created by authorize.php for use during this
 *   operation.
 * @param $projects
 *   A nested array of projects to install into the live webroot, keyed by
 *   project name. Each subarray contains the following keys:
 *   - project: The canonical project short name.
 *   - updater_name: The name of the Drupal\Core\Updater\Updater class to use
 *     for this project.
 *   - local_url: The locally installed location of new code to update with.
 *
 * @return \Symfony\Component\HttpFoundation\Response|null
 *   The result of processing the batch that updates the projects. If this is
 *   an instance of \Symfony\Component\HttpFoundation\Response the calling code
 *   should use that response for the current page request.
 */
function update_authorize_run_update($filetransfer, $projects) {
  $operations = [];
  foreach ($projects as $project_info) {
    $operations[] = [
      'update_authorize_batch_copy_project',
      [
        $project_info['project'],
        $project_info['updater_name'],
        $project_info['local_url'],
        $filetransfer,
      ],
    ];
  }

  $batch = [
    'init_message' => t('Preparing to update your site'),
    'operations' => $operations,
    'finished' => 'update_authorize_update_batch_finished',
    'file' => drupal_get_path('module', 'update') . '/update.authorize.inc',
  ];
  batch_set($batch);

  // Since authorize.php has its own method for setting the page title, set it
  // manually here rather than passing it in to batch_set() as would normally
  // be done.
  $_SESSION['authorize_page_title'] = t('Installing updates');

  // Invoke the batch via authorize.php.
  return system_authorized_batch_process();
}

/**
 * Installs a new project when invoked by authorize.php.
 *
 * Callback for system_authorized_init() in
 * update_manager_install_form_submit().
 *
 * @param FileTransfer $filetransfer
 *   The FileTransfer object created by authorize.php for use during this
 *   operation.
 * @param string $project
 *   The canonical project short name; i.e., the name of the module, theme, or
 *   profile.
 * @param string $updater_name
 *   The name of the Drupal\Core\Updater\Updater class to use for installing
 *   this project.
 * @param string $local_url
 *   The URL to the locally installed temp directory where the project has
 *   already been downloaded and extracted into.
 *
 * @return \Symfony\Component\HttpFoundation\Response|null
 *   The result of processing the batch that installs the project. If this is
 *   an instance of \Symfony\Component\HttpFoundation\Response the calling code
 *   should use that response for the current page request.
 */
function update_authorize_run_install($filetransfer, $project, $updater_name, $local_url) {
  $operations[] = [
    'update_authorize_batch_copy_project',
    [
      $project,
      $updater_name,
      $local_url,
      $filetransfer,
    ],
  ];

  // @todo Instantiate our Updater to set the human-readable title?
  $batch = [
    'init_message' => t('Preparing to install'),
    'operations' => $operations,
    // @todo Use a different finished callback for different messages?
    'finished' => 'update_authorize_install_batch_finished',
    'file' => drupal_get_path('module', 'update') . '/update.authorize.inc',
  ];
  batch_set($batch);

  // Since authorize.php has its own method for setting the page title, set it
  // manually here rather than passing it in to batch_set() as would normally
  // be done.
  $_SESSION['authorize_page_title'] = t('Installing %project', ['%project' => $project]);

  // Invoke the batch via authorize.php.
  return system_authorized_batch_process();
}

/**
 * Implements callback_batch_operation().
 *
 * Copies project to its proper place when authorized to do so.
 *
 * @param string $project
 *   The canonical short name of the project being installed.
 * @param string $updater_name
 *   The name of the Drupal\Core\Updater\Updater class to use for installing
 *   this project.
 * @param string $local_url
 *   The URL to the locally installed temp directory where the project has
 *   already been downloaded and extracted into.
 * @param FileTransfer $filetransfer
 *   The FileTransfer object to use for performing this operation.
 * @param array $context
 *   Reference to an array used for Batch API storage.
 */
function update_authorize_batch_copy_project($project, $updater_name, $local_url, $filetransfer, &$context) {

  // Initialize some variables in the Batch API $context array.
  if (!isset($context['results']['log'])) {
    $context['results']['log'] = [];
  }
  if (!isset($context['results']['log'][$project])) {
    $context['results']['log'][$project] = [];
  }

  if (!isset($context['results']['tasks'])) {
    $context['results']['tasks'] = [];
  }

  // The batch API uses a session, and since all the arguments are serialized
  // and unserialized between requests, although the FileTransfer object itself
  // will be reconstructed, the connection pointer itself will be lost. However,
  // the FileTransfer object will still have the connection variable, even
  // though the connection itself is now gone. So, although it's ugly, we have
  // to unset the connection variable at this point so that the FileTransfer
  // object will re-initiate the actual connection.
  unset($filetransfer->connection);

  if (!empty($context['results']['log'][$project]['#abort'])) {
    $context['finished'] = 1;
    return;
  }

  $updater = new $updater_name($local_url, \Drupal::getContainer()->get('update.root'));

  try {
    if ($updater->isInstalled()) {
      // This is an update.
      $tasks = $updater->update($filetransfer);
    }
    else {
      $tasks = $updater->install($filetransfer);
    }
  }
  catch (UpdaterException $e) {
    _update_batch_create_message($context['results']['log'][$project], t('Error installing / updating'), FALSE);
    _update_batch_create_message($context['results']['log'][$project], $e->getMessage(), FALSE);
    $context['results']['log'][$project]['#abort'] = TRUE;
    return;
  }

  _update_batch_create_message($context['results']['log'][$project], t('Installed %project_name successfully', ['%project_name' => $project]));
  if (!empty($tasks)) {
    $context['results']['tasks'] += $tasks;
  }

  // This particular operation is now complete, even though the batch might
  // have other operations to perform.
  $context['finished'] = 1;
}

/**
 * Batch callback: Performs actions when the authorized update batch is done.
 *
 * This processes the results and stashes them into SESSION such that
 * authorize.php will render a report. Also responsible for putting the site
 * back online and clearing the update status storage after a successful update.
 *
 * @param $success
 *   TRUE if the batch operation was successful; FALSE if there were errors.
 * @param $results
 *   An associative array of results from the batch operation.
 */
function update_authorize_update_batch_finished($success, $results) {
  foreach ($results['log'] as $messages) {
    if (!empty($messages['#abort'])) {
      $success = FALSE;
    }
  }
  $offline = \Drupal::state()->get('system.maintenance_mode');
  if ($success) {
    // Now that the update completed, we need to clear the available update data
    // and recompute our status, so prevent show bogus results.
    _update_authorize_clear_update_status();

    // Take the site out of maintenance mode if it was previously that way.
    if ($offline && isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == FALSE) {
      \Drupal::state()->set('system.maintenance_mode', FALSE);
      $page_message = [
        'message' => t('Update was completed successfully. Your site has been taken out of maintenance mode.'),
        'type' => 'status',
      ];
    }
    else {
      $page_message = [
        'message' => t('Update was completed successfully.'),
        'type' => 'status',
      ];
    }
  }
  elseif (!$offline) {
    $page_message = [
      'message' => t('Update failed! See the log below for more information.'),
      'type' => 'error',
    ];
  }
  else {
    $page_message = [
      'message' => t('Update failed! See the log below for more information. Your site is still in maintenance mode.'),
      'type' => 'error',
    ];
  }
  // Since we're doing an update of existing code, always add a task for
  // running update.php.
  $url = Url::fromRoute('system.db_update');
  $results['tasks'][] = t('Your modules have been downloaded and updated.');
  $results['tasks'][] = [
    '#type' => 'link',
    '#url' => $url,
    '#title' => t('Run database updates'),
    // Since this is being called outsite of the primary front controller,
    // the base_url needs to be set explicitly to ensure that links are
    // relative to the site root.
    // @todo Simplify with https://www.drupal.org/node/2548095
    '#options' => [
      'absolute' => TRUE,
      'base_url' => $GLOBALS['base_url'],
    ],
    '#access' => $url->access(\Drupal::currentUser())
  ];

  // Unset the variable since it is no longer needed.
  unset($_SESSION['maintenance_mode']);

  // Set all these values into the SESSION so authorize.php can display them.
  $_SESSION['authorize_results']['success'] = $success;
  $_SESSION['authorize_results']['page_message'] = $page_message;
  $_SESSION['authorize_results']['messages'] = $results['log'];
  $_SESSION['authorize_results']['tasks'] = $results['tasks'];
  $_SESSION['authorize_page_title'] = t('Update manager');
}

/**
 * Implements callback_batch_finished().
 *
 * Performs actions when the authorized install batch is done.
 *
 * This processes the results and stashes them into SESSION such that
 * authorize.php will render a report. Also responsible for putting the site
 * back online after a successful install if necessary.
 *
 * @param $success
 *   TRUE if the batch operation was a success; FALSE if there were errors.
 * @param $results
 *   An associative array of results from the batch operation.
 */
function update_authorize_install_batch_finished($success, $results) {
  foreach ($results['log'] as $messages) {
    if (!empty($messages['#abort'])) {
      $success = FALSE;
    }
  }
  $offline = \Drupal::state()->get('system.maintenance_mode');
  if ($success) {
    // Take the site out of maintenance mode if it was previously that way.
    if ($offline && isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == FALSE) {
      \Drupal::state()->set('system.maintenance_mode', FALSE);
      $page_message = [
        'message' => t('Installation was completed successfully. Your site has been taken out of maintenance mode.'),
        'type' => 'status',
      ];
    }
    else {
      $page_message = [
        'message' => t('Installation was completed successfully.'),
        'type' => 'status',
      ];
    }
  }
  elseif (!$success && !$offline) {
    $page_message = [
      'message' => t('Installation failed! See the log below for more information.'),
      'type' => 'error',
    ];
  }
  else {
    $page_message = [
      'message' => t('Installation failed! See the log below for more information. Your site is still in maintenance mode.'),
      'type' => 'error',
    ];
  }

  // Unset the variable since it is no longer needed.
  unset($_SESSION['maintenance_mode']);

  // Set all these values into the SESSION so authorize.php can display them.
  $_SESSION['authorize_results']['success'] = $success;
  $_SESSION['authorize_results']['page_message'] = $page_message;
  $_SESSION['authorize_results']['messages'] = $results['log'];
  $_SESSION['authorize_results']['tasks'] = $results['tasks'];
  $_SESSION['authorize_page_title'] = t('Update manager');
}

/**
 * Creates a structure of log messages.
 *
 * @param array $project_results
 *   An associative array of results from the batch operation.
 * @param string $message
 *   A string containing a log message.
 * @param bool $success
 *   (optional) TRUE if the operation the message is about was a success, FALSE
 *   if there were errors. Defaults to TRUE.
 */
function _update_batch_create_message(&$project_results, $message, $success = TRUE) {
  $project_results[] = ['message' => $message, 'success' => $success];
}

/**
 * Clears available update status data.
 *
 * Since this function is run at such a low bootstrap level, the Update Manager
 * module is not loaded. So, we can't just call update_storage_clear(). However,
 * the key-value backend is available, so we just call that.
 *
 * Note that we do not want to delete  items related to currently pending fetch
 * attempts.
 *
 * @see update_authorize_update_batch_finished()
 * @see update_storage_clear()
 */
function _update_authorize_clear_update_status() {
  \Drupal::keyValueExpirable('update')->deleteAll();
  \Drupal::keyValueExpirable('update_available_release')->deleteAll();
}
