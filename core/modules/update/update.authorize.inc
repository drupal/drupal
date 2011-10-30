<?php

/**
 * @file
 * Callbacks and related functions invoked by authorize.php to update projects
 * on the Drupal site. We use the Batch API to actually update each individual
 * project on the site. All of the code in this file is run at a low bootstrap
 * level (modules are not loaded), so these functions cannot assume access to
 * the rest of the update module code.
 */

/**
 * Callback invoked by authorize.php to update existing projects.
 *
 * @param $filetransfer
 *   The FileTransfer object created by authorize.php for use during this
 *   operation.
 * @param $projects
 *   A nested array of projects to install into the live webroot, keyed by
 *   project name. Each subarray contains the following keys:
 *   - 'project': The canonical project short name.
 *   - 'updater_name': The name of the Updater class to use for this project.
 *   - 'local_url': The locally installed location of new code to update with.
 */
function update_authorize_run_update($filetransfer, $projects) {
  $operations = array();
  foreach ($projects as $project => $project_info) {
    $operations[] = array(
      'update_authorize_batch_copy_project',
      array(
        $project_info['project'],
        $project_info['updater_name'],
        $project_info['local_url'],
        $filetransfer,
      ),
    );
  }

  $batch = array(
    'title' => t('Installing updates'),
    'init_message' => t('Preparing to update your site'),
    'operations' => $operations,
    'finished' => 'update_authorize_update_batch_finished',
    'file' => drupal_get_path('module', 'update') . '/update.authorize.inc',
  );

  batch_set($batch);
  // Invoke the batch via authorize.php.
  system_authorized_batch_process();
}

/**
 * Callback invoked by authorize.php to install a new project.
 *
 * @param FileTransfer $filetransfer
 *   The FileTransfer object created by authorize.php for use during this
 *   operation.
 * @param string $project
 *   The canonical project short name (e.g. {system}.name).
 * @param string $updater_name
 *   The name of the Updater class to use for installing this project.
 * @param string $local_url
 *   The URL to the locally installed temp directory where the project has
 *   already been downloaded and extracted into.
 */
function update_authorize_run_install($filetransfer, $project, $updater_name, $local_url) {
  $operations[] = array(
    'update_authorize_batch_copy_project',
    array(
      $project,
      $updater_name,
      $local_url,
      $filetransfer,
    ),
  );

  // @todo Instantiate our Updater to set the human-readable title?
  $batch = array(
    'title' => t('Installing %project', array('%project' => $project)),
    'init_message' => t('Preparing to install'),
    'operations' => $operations,
    // @todo Use a different finished callback for different messages?
    'finished' => 'update_authorize_install_batch_finished',
    'file' => drupal_get_path('module', 'update') . '/update.authorize.inc',
  );
  batch_set($batch);

  // Invoke the batch via authorize.php.
  system_authorized_batch_process();
}

/**
 * Copy a project to its proper place when authorized with elevated privileges.
 *
 * @param string $project
 *   The canonical short name of the project being installed.
 * @param string $updater_name
 *   The name of the Updater class to use for installing this project.
 * @param string $local_url
 *   The URL to the locally installed temp directory where the project has
 *   already been downloaded and extracted into.
 * @param FileTransfer $filetransfer
 *   The FileTransfer object to use for performing this operation.
 * @param array $context
 *   Reference to an array used for BatchAPI storage.
 */
function update_authorize_batch_copy_project($project, $updater_name, $local_url, $filetransfer, &$context) {

  // Initialize some variables in the Batch API $context array.
  if (!isset($context['results']['log'])) {
    $context['results']['log'] = array();
  }
  if (!isset($context['results']['log'][$project])) {
    $context['results']['log'][$project] = array();
  }

  if (!isset($context['results']['tasks'])) {
    $context['results']['tasks'] = array();
  }

  /**
   * The batch API uses a session, and since all the arguments are serialized
   * and unserialized between requests, although the FileTransfer object
   * itself will be reconstructed, the connection pointer itself will be lost.
   * However, the FileTransfer object will still have the connection variable,
   * even though the connection itself is now gone. So, although it's ugly, we
   * have to unset the connection variable at this point so that the
   * FileTransfer object will re-initiate the actual connection.
   */
  unset($filetransfer->connection);

  if (!empty($context['results']['log'][$project]['#abort'])) {
    $context['finished'] = 1;
    return;
  }

  $updater = new $updater_name($local_url);

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

  _update_batch_create_message($context['results']['log'][$project], t('Installed %project_name successfully', array('%project_name' => $project)));
  if (!empty($tasks)) {
    $context['results']['tasks'] += $tasks;
  }

  // This particular operation is now complete, even though the batch might
  // have other operations to perform.
  $context['finished'] = 1;
}

/**
 * Batch callback for when the authorized update batch is finished.
 *
 * This processes the results and stashes them into SESSION such that
 * authorize.php will render a report. Also responsible for putting the site
 * back online and clearing the update status cache after a successful update.
 */
function update_authorize_update_batch_finished($success, $results) {
  foreach ($results['log'] as $project => $messages) {
    if (!empty($messages['#abort'])) {
      $success = FALSE;
    }
  }
  $offline = variable_get('maintenance_mode', FALSE);
  if ($success) {
    // Now that the update completed, we need to clear the cache of available
    // update data and recompute our status, so prevent show bogus results.
    _update_authorize_clear_update_status();

    // Take the site out of maintenance mode if it was previously that way.
    if ($offline && isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == FALSE) {
      variable_set('maintenance_mode', FALSE);
      $page_message = array(
        'message' => t('Update was completed successfully. Your site has been taken out of maintenance mode.'),
        'type' => 'status',
      );
    }
    else {
      $page_message = array(
        'message' => t('Update was completed successfully.'),
        'type' => 'status',
      );
    }
  }
  elseif (!$offline) {
    $page_message = array(
      'message' => t('Update failed! See the log below for more information.'),
      'type' => 'error',
    );
  }
  else {
    $page_message = array(
      'message' => t('Update failed! See the log below for more information. Your site is still in maintenance mode.'),
      'type' => 'error',
    );
  }
  // Since we're doing an update of existing code, always add a task for
  // running update.php.
  $results['tasks'][] = t('Your modules have been downloaded and updated.');
  $results['tasks'][] = t('<a href="@update">Run database updates</a>', array('@update' => base_path() . 'update.php'));

  // Unset the variable since it is no longer needed.
  unset($_SESSION['maintenance_mode']);

  // Set all these values into the SESSION so authorize.php can display them.
  $_SESSION['authorize_results']['success'] = $success;
  $_SESSION['authorize_results']['page_message'] = $page_message;
  $_SESSION['authorize_results']['messages'] = $results['log'];
  $_SESSION['authorize_results']['tasks'] = $results['tasks'];
  $_SESSION['authorize_operation']['page_title'] = t('Update manager');
}

/**
 * Batch callback for when the authorized install batch is finished.
 *
 * This processes the results and stashes them into SESSION such that
 * authorize.php will render a report. Also responsible for putting the site
 * back online after a successful install if necessary.
 */
function update_authorize_install_batch_finished($success, $results) {
  foreach ($results['log'] as $project => $messages) {
    if (!empty($messages['#abort'])) {
      $success = FALSE;
    }
  }
  $offline = variable_get('maintenance_mode', FALSE);
  if ($success) {
    // Take the site out of maintenance mode if it was previously that way.
    if ($offline && isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == FALSE) {
      variable_set('maintenance_mode', FALSE);
      $page_message = array(
        'message' => t('Installation was completed successfully. Your site has been taken out of maintenance mode.'),
        'type' => 'status',
      );
    }
    else {
      $page_message = array(
        'message' => t('Installation was completed successfully.'),
        'type' => 'status',
      );
    }
  }
  elseif (!$success && !$offline) {
    $page_message = array(
      'message' => t('Installation failed! See the log below for more information.'),
      'type' => 'error',
    );
  }
  else {
    $page_message = array(
      'message' => t('Installation failed! See the log below for more information. Your site is still in maintenance mode.'),
      'type' => 'error',
    );
  }

  // Unset the variable since it is no longer needed.
  unset($_SESSION['maintenance_mode']);

  // Set all these values into the SESSION so authorize.php can display them.
  $_SESSION['authorize_results']['success'] = $success;
  $_SESSION['authorize_results']['page_message'] = $page_message;
  $_SESSION['authorize_results']['messages'] = $results['log'];
  $_SESSION['authorize_results']['tasks'] = $results['tasks'];
  $_SESSION['authorize_operation']['page_title'] = t('Update manager');
}

/**
 * Helper function to create a structure of log messages.
 *
 * @param array $project_results
 * @param string $message
 * @param bool $success
 */
function _update_batch_create_message(&$project_results, $message, $success = TRUE) {
  $project_results[] = array('message' => $message, 'success' => $success);
}

/**
 * Private helper function to clear cached available update status data.
 *
 * Since this function is run at such a low bootstrap level, update.module is
 * not loaded. So, we can't just call _update_cache_clear(). However, the
 * database is bootstrapped, so we can do a query ourselves to clear out what
 * we want to clear.
 *
 * Note that we do not want to just truncate the table, since that would
 * remove items related to currently pending fetch attempts.
 *
 * @see update_authorize_update_batch_finished()
 * @see _update_cache_clear()
 */
function _update_authorize_clear_update_status() {
  $query = db_delete('cache_update');
  $query->condition(
    db_or()
    ->condition('cid', 'update_project_%', 'LIKE')
    ->condition('cid', 'available_releases::%', 'LIKE')
  );
  $query->execute();
}
