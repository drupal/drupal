<?php

/**
 * @file
 * Administrative screens and processing functions of the Update Manager module.
 *
 * This allows site administrators with the 'administer software updates'
 * permission to either upgrade existing projects, or download and install new
 * ones, so long as the killswitch setting ('allow_authorize_operations') is
 * not FALSE.
 *
 * To install new code, the administrator is prompted for either the URL of an
 * archive file, or to directly upload the archive file. The archive is loaded
 * into a temporary location, extracted, and verified. If everything is
 * successful, the user is redirected to authorize.php to type in file transfer
 * credentials and authorize the installation to proceed with elevated
 * privileges, such that the extracted files can be copied out of the temporary
 * location and into the live web root.
 *
 * Updating existing code is a more elaborate process. The first step is a
 * selection form where the user is presented with a table of installed projects
 * that are missing newer releases. The user selects which projects they wish to
 * update, and presses the "Download updates" button to continue. This sets up a
 * batch to fetch all the selected releases, and redirects to
 * admin/update/download to display the batch progress bar as it runs. Each
 * batch operation is responsible for downloading a single file, extracting the
 * archive, and verifying the contents. If there are any errors, the user is
 * redirected back to the first page with the error messages. If all downloads
 * were extracted and verified, the user is instead redirected to
 * admin/update/ready, a landing page which reminds them to backup their
 * database and asks if they want to put the site offline during the update.
 * Once the user presses the "Install updates" button, they are redirected to
 * authorize.php to supply their web root file access credentials. The
 * authorized operation (which lives in update.authorize.inc) sets up a batch to
 * copy each extracted update from the temporary location into the live web
 * root.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Batch callback: Performs actions when the download batch is completed.
 *
 * @param $success
 *   TRUE if the batch operation was successful, FALSE if there were errors.
 * @param $results
 *   An associative array of results from the batch operation.
 */
function update_manager_download_batch_finished($success, $results) {
  if (!empty($results['errors'])) {
    $item_list = array(
      '#theme' => 'item_list',
      '#title' => t('Downloading updates failed:'),
      '#items' => $results['errors'],
    );
    drupal_set_message(drupal_render($item_list), 'error');
  }
  elseif ($success) {
    drupal_set_message(t('Updates downloaded successfully.'));
    $_SESSION['update_manager_update_projects'] = $results['projects'];
    return new RedirectResponse(\Drupal::url('update.confirmation_page', [], ['absolute' => TRUE]));
  }
  else {
    // Ideally we're catching all Exceptions, so they should never see this,
    // but just in case, we have to tell them something.
    drupal_set_message(t('Fatal error trying to download.'), 'error');
  }
}

/**
 * Checks for file transfer backends and prepares a form fragment about them.
 *
 * @param array $form
 *   Reference to the form array we're building.
 * @param string $operation
 *   The update manager operation we're in the middle of. Can be either 'update'
 *   or 'install'. Use to provide operation-specific interface text.
 *
 * @return
 *   TRUE if the update manager should continue to the next step in the
 *   workflow, or FALSE if we've hit a fatal configuration and must halt the
 *   workflow.
 */
function _update_manager_check_backends(&$form, $operation) {
  // If file transfers will be performed locally, we do not need to display any
  // warnings or notices to the user and should automatically continue the
  // workflow, since we won't be using a FileTransfer backend that requires
  // user input or a specific server configuration.
  if (update_manager_local_transfers_allowed()) {
    return TRUE;
  }

  // Otherwise, show the available backends.
  $form['available_backends'] = array(
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );

  $available_backends = drupal_get_filetransfer_info();
  if (empty($available_backends)) {
    if ($operation == 'update') {
      $form['available_backends']['#markup'] = t('Your server does not support updating modules and themes from this interface. Instead, update modules and themes by uploading the new versions directly to the server, as described in the <a href="@handbook_url">handbook</a>.', array('@handbook_url' => 'http://drupal.org/getting-started/install-contrib'));
    }
    else {
      $form['available_backends']['#markup'] = t('Your server does not support installing modules and themes from this interface. Instead, install modules and themes by uploading them directly to the server, as described in the <a href="@handbook_url">handbook</a>.', array('@handbook_url' => 'http://drupal.org/getting-started/install-contrib'));
    }
    return FALSE;
  }

  $backend_names = array();
  foreach ($available_backends as $backend) {
    $backend_names[] = $backend['title'];
  }
  if ($operation == 'update') {
    $form['available_backends']['#markup'] = \Drupal::translation()->formatPlural(
      count($available_backends),
      'Updating modules and themes requires <strong>@backends access</strong> to your server. See the <a href="@handbook_url">handbook</a> for other update methods.',
      'Updating modules and themes requires access to your server via one of the following methods: <strong>@backends</strong>. See the <a href="@handbook_url">handbook</a> for other update methods.',
      array(
        '@backends' => implode(', ', $backend_names),
        '@handbook_url' => 'http://drupal.org/getting-started/install-contrib',
      ));
  }
  else {
    $form['available_backends']['#markup'] = \Drupal::translation()->formatPlural(
      count($available_backends),
      'Installing modules and themes requires <strong>@backends access</strong> to your server. See the <a href="@handbook_url">handbook</a> for other installation methods.',
      'Installing modules and themes requires access to your server via one of the following methods: <strong>@backends</strong>. See the <a href="@handbook_url">handbook</a> for other installation methods.',
      array(
        '@backends' => implode(', ', $backend_names),
        '@handbook_url' => 'http://drupal.org/getting-started/install-contrib',
      ));
  }
  return TRUE;
}

/**
 * Unpacks a downloaded archive file.
 *
 * @param string $file
 *   The filename of the archive you wish to extract.
 * @param string $directory
 *   The directory you wish to extract the archive into.
 *
 * @return Archiver
 *   The Archiver object used to extract the archive.
 *
 * @throws Exception
 */
function update_manager_archive_extract($file, $directory) {
  $archiver = archiver_get_archiver($file);
  if (!$archiver) {
    throw new Exception(t('Cannot extract %file, not a valid archive.', array ('%file' => $file)));
  }

  // Remove the directory if it exists, otherwise it might contain a mixture of
  // old files mixed with the new files (e.g. in cases where files were removed
  // from a later release).
  $files = $archiver->listContents();

  // Unfortunately, we can only use the directory name to determine the project
  // name. Some archivers list the first file as the directory (i.e., MODULE/)
  // and others list an actual file (i.e., MODULE/README.TXT).
  $project = strtok($files[0], '/\\');

  $extract_location = $directory . '/' . $project;
  if (file_exists($extract_location)) {
    file_unmanaged_delete_recursive($extract_location);
  }

  $archiver->extract($directory);
  return $archiver;
}

/**
 * Verifies an archive after it has been downloaded and extracted.
 *
 * This function is responsible for invoking hook_verify_update_archive().
 *
 * @param string $project
 *   The short name of the project to download.
 * @param string $archive_file
 *   The filename of the unextracted archive.
 * @param string $directory
 *   The directory that the archive was extracted into.
 *
 * @return array
 *   An array of error messages to display if the archive was invalid. If there
 *   are no errors, it will be an empty array.
 */
function update_manager_archive_verify($project, $archive_file, $directory) {
  return \Drupal::moduleHandler()->invokeAll('verify_update_archive', array($project, $archive_file, $directory));
}

/**
 * Copies a file from the specified URL to the temporary directory for updates.
 *
 * Returns the local path if the file has already been downloaded.
 *
 * @param $url
 *   The URL of the file on the server.
 *
 * @return string
 *   Path to local file.
 */
function update_manager_file_get($url) {
  $parsed_url = parse_url($url);
  $remote_schemes = array('http', 'https', 'ftp', 'ftps', 'smb', 'nfs');
  if (!in_array($parsed_url['scheme'], $remote_schemes)) {
    // This is a local file, just return the path.
    return drupal_realpath($url);
  }

  // Check the cache and download the file if needed.
  $cache_directory = _update_manager_cache_directory();
  $local = $cache_directory . '/' . drupal_basename($parsed_url['path']);

  if (!file_exists($local) || update_delete_file_if_stale($local)) {
    return system_retrieve_file($url, $local, FALSE, FILE_EXISTS_REPLACE);
  }
  else {
    return $local;
  }
}

/**
 * Implements callback_batch_operation().
 *
 * Downloads, unpacks, and verifies a project.
 *
 * This function assumes that the provided URL points to a file archive of some
 * sort. The URL can have any scheme that we have a file stream wrapper to
 * support. The file is downloaded to a local cache.
 *
 * @param string $project
 *   The short name of the project to download.
 * @param string $url
 *   The URL to download a specific project release archive file.
 * @param array $context
 *   Reference to an array used for Batch API storage.
 *
 * @see update_manager_download_page()
 */
function update_manager_batch_project_get($project, $url, &$context) {
  // This is here to show the user that we are in the process of downloading.
  if (!isset($context['sandbox']['started'])) {
    $context['sandbox']['started'] = TRUE;
    $context['message'] = t('Downloading %project', array('%project' => $project));
    $context['finished'] = 0;
    return;
  }

  // Actually try to download the file.
  if (!($local_cache = update_manager_file_get($url))) {
    $context['results']['errors'][$project] = t('Failed to download %project from %url', array('%project' => $project, '%url' => $url));
    return;
  }

  // Extract it.
  $extract_directory = _update_manager_extract_directory();
  try {
    update_manager_archive_extract($local_cache, $extract_directory);
  }
  catch (Exception $e) {
    $context['results']['errors'][$project] = $e->getMessage();
    return;
  }

  // Verify it.
  $archive_errors = update_manager_archive_verify($project, $local_cache, $extract_directory);
  if (!empty($archive_errors)) {
    // We just need to make sure our array keys don't collide, so use the
    // numeric keys from the $archive_errors array.
    foreach ($archive_errors as $key => $error) {
      $context['results']['errors']["$project-$key"] = $error;
    }
    return;
  }

  // Yay, success.
  $context['results']['projects'][$project] = $url;
  $context['finished'] = 1;
}

/**
 * Determines if file transfers will be performed locally.
 *
 * If the server is configured such that webserver-created files have the same
 * owner as the configuration directory (e.g., sites/default) where new code
 * will eventually be installed, the update manager can transfer files entirely
 * locally, without changing their ownership (in other words, without prompting
 * the user for FTP, SSH or other credentials).
 *
 * This server configuration is an inherent security weakness because it allows
 * a malicious webserver process to append arbitrary PHP code and then execute
 * it. However, it is supported here because it is a common configuration on
 * shared hosting, and there is nothing Drupal can do to prevent it.
 *
 * @return
 *   TRUE if local file transfers are allowed on this server, or FALSE if not.
 *
 * @see install_check_requirements()
 */
function update_manager_local_transfers_allowed() {
  // Compare the owner of a webserver-created temporary file to the owner of
  // the configuration directory to determine if local transfers will be
  // allowed.
  $temporary_file = drupal_tempnam('temporary://', 'update_');
  $local_transfers_allowed = fileowner($temporary_file) === fileowner(conf_path());

  // Clean up. If this fails, we can ignore it (since this is just a temporary
  // file anyway).
  @drupal_unlink($temporary_file);

  return $local_transfers_allowed;
}
