<?php

/**
 * @file
 * Administrative screens and processing functions of the Update Manager module.
 *
 * This allows site administrators with the 'administer software updates'
 * permission to either upgrade existing projects, or download and install new
 * ones, so long as the kill switch setting ('allow_authorize_operations') is
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

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\Url;
use Psr\Http\Client\ClientExceptionInterface;
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
    $item_list = [
      '#theme' => 'item_list',
      '#title' => t('Downloading updates failed:'),
      '#items' => $results['errors'],
    ];
    \Drupal::messenger()->addError(\Drupal::service('renderer')->render($item_list));
  }
  elseif ($success) {
    \Drupal::messenger()->addStatus(t('Updates downloaded successfully.'));
    \Drupal::request()->getSession()->set('update_manager_update_projects', $results['projects']);
    return new RedirectResponse(Url::fromRoute('update.confirmation_page', [], ['absolute' => TRUE])->toString());
  }
  else {
    // Ideally we're catching all Exceptions, so they should never see this,
    // but just in case, we have to tell them something.
    \Drupal::messenger()->addError(t('Fatal error trying to download.'));
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
 * @return bool
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
  $form['available_backends'] = [
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  ];

  $available_backends = drupal_get_filetransfer_info();
  if (empty($available_backends)) {
    if ($operation == 'update') {
      $form['available_backends']['#markup'] = t('Your server does not support updating modules and themes from this interface. Instead, update modules and themes by uploading the new versions directly to the server, as documented in <a href=":doc_url">Extending Drupal</a>.', [':doc_url' => 'https://www.drupal.org/docs/extending-drupal/overview']);
    }
    else {
      $form['available_backends']['#markup'] = t('Your server does not support adding modules and themes from this interface. Instead, add modules and themes by uploading them directly to the server, as documented in <a href=":doc_url">Extending Drupal</a>.', [':doc_url' => 'https://www.drupal.org/docs/extending-drupal/overview']);
    }
    return FALSE;
  }

  $backend_names = [];
  foreach ($available_backends as $backend) {
    $backend_names[] = $backend['title'];
  }
  if ($operation == 'update') {
    $form['available_backends']['#markup'] = \Drupal::translation()->formatPlural(
      count($available_backends),
      'Updating modules and themes requires <strong>@backends access</strong> to your server. See <a href=":doc_url">Extending Drupal</a> for other update methods.',
      'Updating modules and themes requires access to your server via one of the following methods: <strong>@backends</strong>. See <a href=":doc_url">Extending Drupal</a> for other update methods.',
      [
        '@backends' => implode(', ', $backend_names),
        ':doc_url' => 'https://www.drupal.org/docs/extending-drupal/overview',
      ]);
  }
  else {
    $form['available_backends']['#markup'] = \Drupal::translation()->formatPlural(
      count($available_backends),
      'Adding modules and themes requires <strong>@backends access</strong> to your server. See <a href=":doc_url">Extending Drupal</a> for other methods.',
      'Adding modules and themes requires access to your server via one of the following methods: <strong>@backends</strong>. See <a href=":doc_url">Extending Drupal</a> for other methods.',
      [
        '@backends' => implode(', ', $backend_names),
        ':doc_url' => 'https://www.drupal.org/docs/extending-drupal/overview',
      ]);
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
 * @return \Drupal\Core\Archiver\ArchiverInterface
 *   The Archiver object used to extract the archive.
 *
 * @throws Exception
 */
function update_manager_archive_extract($file, $directory) {
  /** @var \Drupal\Core\Archiver\ArchiverInterface $archiver */
  $archiver = \Drupal::service('plugin.manager.archiver')->getInstance([
    'filepath' => $file,
  ]);
  if (!$archiver) {
    throw new Exception("Cannot extract '$file', not a valid archive");
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
    try {
      \Drupal::service('file_system')->deleteRecursive($extract_location);
    }
    catch (FileException $e) {
      // Ignore failed deletes.
    }
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
 *   The filename of the un-extracted archive.
 * @param string $directory
 *   The directory that the archive was extracted into.
 *
 * @return array
 *   An array of error messages to display if the archive was invalid. If there
 *   are no errors, it will be an empty array.
 */
function update_manager_archive_verify($project, $archive_file, $directory) {
  return \Drupal::moduleHandler()->invokeAll('verify_update_archive', [$project, $archive_file, $directory]);
}

/**
 * Copies a file from the specified URL to the temporary directory for updates.
 *
 * Returns the local path if the file has already been downloaded.
 *
 * @param $url
 *   The URL of the file on the server.
 *
 * @return string|false
 *   Path to local file, or FALSE if it could not be retrieved.
 */
function update_manager_file_get($url) {
  $parsed_url = parse_url($url);
  $remote_schemes = ['http', 'https', 'ftp', 'ftps', 'smb', 'nfs'];
  if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], $remote_schemes)) {
    // This is a local file, just return the path.
    return \Drupal::service('file_system')->realpath($url);
  }

  // Check the cache and download the file if needed.
  $cache_directory = _update_manager_cache_directory();
  $local = $cache_directory . '/' . \Drupal::service('file_system')->basename($parsed_url['path']);

  if (!file_exists($local) || update_delete_file_if_stale($local)) {
    try {
      $data = (string) \Drupal::httpClient()->get($url)->getBody();
      return \Drupal::service('file_system')->saveData($data, $local, FileExists::Replace);
    }
    catch (ClientExceptionInterface $exception) {
      \Drupal::messenger()->addError(t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
    }
    catch (FileException | InvalidStreamWrapperException $e) {
      \Drupal::messenger()->addError(t('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
    }
    return FALSE;
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
    $context['message'] = t('Downloading %project', ['%project' => $project]);
    $context['finished'] = 0;
    return;
  }

  // Actually try to download the file.
  if (!($local_cache = update_manager_file_get($url))) {
    $context['results']['errors'][$project] = t('Failed to download %project from %url', ['%project' => $project, '%url' => $url]);
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
 * @return bool
 *   TRUE if local file transfers are allowed on this server, or FALSE if not.
 *
 * @see install_check_requirements()
 */
function update_manager_local_transfers_allowed() {
  $file_system = \Drupal::service('file_system');
  // Compare the owner of a webserver-created temporary file to the owner of
  // the configuration directory to determine if local transfers will be
  // allowed.
  $temporary_file = \Drupal::service('file_system')->tempnam('temporary://', 'update_');
  $site_path = \Drupal::getContainer()->getParameter('site.path');
  $local_transfers_allowed = fileowner($temporary_file) === fileowner($site_path);

  // Clean up. If this fails, we can ignore it (since this is just a temporary
  // file anyway).
  @$file_system->unlink($temporary_file);

  return $local_transfers_allowed;
}
