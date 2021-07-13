<?php

/**
 * @file
 * Hooks related to the File management system.
 */

use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Control access to private file downloads and specify HTTP headers.
 *
 * This hook allows modules to enforce permissions on file downloads whenever
 * Drupal is handling file download, as opposed to the web server bypassing
 * Drupal and returning the file from a public directory. Modules can also
 * provide headers to specify information like the file's name or MIME type.
 *
 * @param $uri
 *   The URI of the file.
 *
 * @return
 *   If the user does not have permission to access the file, return -1. If the
 *   user has permission, return an array with the appropriate headers. If the
 *   file is not controlled by the current module, the return value should be
 *   NULL.
 *
 * @see \Drupal\system\FileDownloadController::download()
 */
function hook_file_download($uri) {
  // Check to see if this is a config download.
  $scheme = StreamWrapperManager::getScheme($uri);
  $target = StreamWrapperManager::getTarget($uri);
  if ($scheme == 'temporary' && $target == 'config.tar.gz') {
    return [
      'Content-disposition' => 'attachment; filename="config.tar.gz"',
    ];
  }
}

/**
 * Alter the URL to a file.
 *
 * This hook is called from \Drupal\Core\File\FileUrlGenerator::generate(),
 * and is called fairly frequently (10+ times per page), depending on how many
 * files there are in a given page.
 * If CSS and JS aggregation are disabled, this can become very frequently
 * (50+ times per page) so performance is critical.
 *
 * This function should alter the URI, if it wants to rewrite the file URL.
 *
 * @param $uri
 *   The URI to a file for which we need an external URL, or the path to a
 *   shipped file.
 */
function hook_file_url_alter(&$uri) {
  $user = \Drupal::currentUser();

  // User 1 will always see the local file in this example.
  if ($user->id() == 1) {
    return;
  }

  $cdn1 = 'http://cdn1.example.com';
  $cdn2 = 'http://cdn2.example.com';
  $cdn_extensions = ['css', 'js', 'gif', 'jpg', 'jpeg', 'png'];

  // Most CDNs don't support private file transfers without a lot of hassle,
  // so don't support this in the common case.
  $schemes = ['public'];

  /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
  $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

  $scheme = $stream_wrapper_manager::getScheme($uri);

  // Only serve shipped files and public created files from the CDN.
  if (!$scheme || in_array($scheme, $schemes)) {
    // Shipped files.
    if (!$scheme) {
      $path = $uri;
    }
    // Public created files.
    else {
      $wrapper = $stream_wrapper_manager->getViaScheme($scheme);
      $path = $wrapper->getDirectoryPath() . '/' . $stream_wrapper_manager::getTarget($uri);
    }

    // Clean up Windows paths.
    $path = str_replace('\\', '/', $path);

    // Serve files with one of the CDN extensions from CDN 1, all others from
    // CDN 2.
    $pathinfo = pathinfo($path);
    if (isset($pathinfo['extension']) && in_array($pathinfo['extension'], $cdn_extensions)) {
      $uri = $cdn1 . '/' . $path;
    }
    else {
      $uri = $cdn2 . '/' . $path;
    }
  }
}

/**
 * Alter MIME type mappings used to determine MIME type from a file extension.
 *
 * Invoked by
 * \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::guessMimeType(). It is
 * used to allow modules to add to or modify the default mapping from
 * \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::$defaultMapping.
 *
 * @param $mapping
 *   An array of mimetypes correlated to the extensions that relate to them.
 *   The array has 'mimetypes' and 'extensions' elements, each of which is an
 *   array.
 *
 * @see \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::guessMimeType()
 * @see \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::$defaultMapping
 */
function hook_file_mimetype_mapping_alter(&$mapping) {
  // Add new MIME type 'drupal/info'.
  $mapping['mimetypes']['example_info'] = 'drupal/info';
  // Add new extension '.info.yml' and map it to the 'drupal/info' MIME type.
  $mapping['extensions']['info'] = 'example_info';
  // Override existing extension mapping for '.ogg' files.
  $mapping['extensions']['ogg'] = 189;
}

/**
 * Alter archiver information declared by other modules.
 *
 * See hook_archiver_info() for a description of archivers and the archiver
 * information structure.
 *
 * @param $info
 *   Archiver information to alter (return values from hook_archiver_info()).
 */
function hook_archiver_info_alter(&$info) {
  $info['tar']['extensions'][] = 'tgz';
}

/**
 * Register information about FileTransfer classes provided by a module.
 *
 * The FileTransfer class allows transferring files over a specific type of
 * connection. Core provides classes for FTP and SSH. Contributed modules are
 * free to extend the FileTransfer base class to add other connection types,
 * and if these classes are registered via hook_filetransfer_info(), those
 * connection types will be available to site administrators using the Update
 * manager when they are redirected to the authorize.php script to authorize
 * the file operations.
 *
 * @return array
 *   Nested array of information about FileTransfer classes. Each key is a
 *   FileTransfer type (not human readable, used for form elements and
 *   variable names, etc), and the values are subarrays that define properties
 *   of that type. The keys in each subarray are:
 *   - 'title': Required. The human-readable name of the connection type.
 *   - 'class': Required. The name of the FileTransfer class. The constructor
 *     will always be passed the full path to the root of the site that should
 *     be used to restrict where file transfer operations can occur (the $jail)
 *     and an array of settings values returned by the settings form.
 *   - 'weight': Optional. Integer weight used for sorting connection types on
 *     the authorize.php form.
 *
 * @see \Drupal\Core\FileTransfer\FileTransfer
 * @see authorize.php
 * @see hook_filetransfer_info_alter()
 * @see drupal_get_filetransfer_info()
 */
function hook_filetransfer_info() {
  $info['sftp'] = [
    'title' => t('SFTP (Secure FTP)'),
    'class' => 'Drupal\Core\FileTransfer\SFTP',
    'weight' => 10,
  ];
  return $info;
}

/**
 * Alter the FileTransfer class registry.
 *
 * @param array $filetransfer_info
 *   Reference to a nested array containing information about the FileTransfer
 *   class registry.
 *
 * @see hook_filetransfer_info()
 */
function hook_filetransfer_info_alter(&$filetransfer_info) {
  // Remove the FTP option entirely.
  unset($filetransfer_info['ftp']);
  // Make sure the SSH option is listed first.
  $filetransfer_info['ssh']['weight'] = -10;
}

/**
 * @} End of "addtogroup hooks".
 */
