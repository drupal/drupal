<?php

/**
 * @file
 * API for handling file uploads and server file management.
 */

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Component\FileSystem\FileSystem as ComponentFileSystem;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Default mode for new directories.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::CHMOD_DIRECTORY.
 *
 * @see \Drupal\Core\File\FileSystemInterface::chmod()
 * @see https://www.drupal.org/node/2418133
 */
const FILE_CHMOD_DIRECTORY = FileSystem::CHMOD_DIRECTORY;

/**
 * Default mode for new files.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::CHMOD_FILE.
 *
 * @see \Drupal\Core\File\FileSystemInterface::chmod()
 * @see https://www.drupal.org/node/2418133
 */
const FILE_CHMOD_FILE = FileSystem::CHMOD_FILE;

/**
 * @defgroup file File interface
 * @{
 * Common file handling functions.
 */

/**
 * Flag used to create a directory if not present.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY.
 */
const FILE_CREATE_DIRECTORY = FileSystemInterface::CREATE_DIRECTORY;

/**
 * Flag used to indicate file permissions may be changed.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS.
 */
const FILE_MODIFY_PERMISSIONS = FileSystemInterface::MODIFY_PERMISSIONS;

/**
 * Flag for dealing with existing files: Appends number until name is unique.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME.
 */
const FILE_EXISTS_RENAME = FileSystemInterface::EXISTS_RENAME;

/**
 * Flag for dealing with existing files: Replace the existing file.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE.
 */
const FILE_EXISTS_REPLACE = FileSystemInterface::EXISTS_REPLACE;

/**
 * Flag for dealing with existing files: Do nothing and return FALSE.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::EXISTS_ERROR.
 */
const FILE_EXISTS_ERROR = FileSystemInterface::EXISTS_ERROR;

/**
 * Indicates that the file is permanent and should not be deleted.
 *
 * Temporary files older than the system.file.temporary_maximum_age
 * configuration value will be, if clean-up not disabled, removed during cron
 * runs, but permanent files will not be removed during the file garbage
 * collection process.
 */
const FILE_STATUS_PERMANENT = 1;

/**
 * Returns the scheme of a URI (e.g. a stream).
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getScheme()
 *   instead.
 *
 * @see https://www.drupal.org/node/3035273
 */
function file_uri_scheme($uri) {
  @trigger_error('file_uri_scheme() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getScheme() instead. See https://www.drupal.org/node/3035273', E_USER_DEPRECATED);
  return StreamWrapperManager::getScheme($uri);
}

/**
 * Checks that the scheme of a stream URI is valid.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::isValidScheme()
 *   instead.
 *
 * @see https://www.drupal.org/node/3035273
 */
function file_stream_wrapper_valid_scheme($scheme) {
  @trigger_error('file_stream_wrapper_valid_scheme() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::isValidScheme() instead. See https://www.drupal.org/node/3035273', E_USER_DEPRECATED);
  return \Drupal::service('stream_wrapper_manager')->isValidScheme($scheme);
}

/**
 * Returns the part of a URI after the schema.
 *
 * @param string $uri
 *   A stream, referenced as "scheme://target" or "data:target".
 *
 * @return string|bool
 *   A string containing the target (path), or FALSE if none.
 *   For example, the URI "public://sample/test.txt" would return
 *   "sample/test.txt".
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getTarget()
 *   instead.
 *
 * @see https://www.drupal.org/node/3035273
 */
function file_uri_target($uri) {
  @trigger_error('file_uri_target() is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getTarget() instead. See https://www.drupal.org/node/3035273', E_USER_DEPRECATED);
  return StreamWrapperManager::getTarget($uri);
}

/**
 * Gets the default file stream implementation.
 *
 * @return string
 *   'public', 'private' or any other file scheme defined as the default.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal::config('system.file')->get('default_scheme') instead.
 *
 * @see https://www.drupal.org/node/3049030
 */
function file_default_scheme() {
  @trigger_error('file_default_scheme() is deprecated in drupal:8.8.0. It will be removed from drupal:9.0.0. Use \Drupal::config(\'system.file\')->get(\'default_scheme\') instead. See https://www.drupal.org/node/3049030', E_USER_DEPRECATED);
  return \Drupal::config('system.file')->get('default_scheme');
}

/**
 * Normalizes a URI by making it syntactically correct.
 *
 * A stream is referenced as "scheme://target".
 *
 * The following actions are taken:
 * - Remove trailing slashes from target
 * - Trim erroneous leading slashes from target. e.g. ":///" becomes "://".
 *
 * @param string $uri
 *   String reference containing the URI to normalize.
 *
 * @return string
 *   The normalized URI.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::normalizeUri()
 *   instead.
 *
 * @see https://www.drupal.org/node/3035273
 */
function file_stream_wrapper_uri_normalize($uri) {
  @trigger_error('file_stream_wrapper_uri_normalize() is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::normalizeUri() instead. See https://www.drupal.org/node/3035273', E_USER_DEPRECATED);
  return \Drupal::service('stream_wrapper_manager')->normalizeUri($uri);
}

/**
 * Creates a web-accessible URL for a stream to an external or local file.
 *
 * Compatibility: normal paths and stream wrappers.
 *
 * There are two kinds of local files:
 * - "managed files", i.e. those stored by a Drupal-compatible stream wrapper.
 *   These are files that have either been uploaded by users or were generated
 *   automatically (for example through CSS aggregation).
 * - "shipped files", i.e. those outside of the files directory, which ship as
 *   part of Drupal core or contributed modules or themes.
 *
 * @param string $uri
 *   The URI to a file for which we need an external URL, or the path to a
 *   shipped file.
 *
 * @return string
 *   A string containing a URL that may be used to access the file.
 *   If the provided string already contains a preceding 'http', 'https', or
 *   '/', nothing is done and the same string is returned. If a stream wrapper
 *   could not be found to generate an external URL, then FALSE is returned.
 *
 * @see https://www.drupal.org/node/515192
 * @see file_url_transform_relative()
 */
function file_create_url($uri) {
  // Allow the URI to be altered, e.g. to serve a file from a CDN or static
  // file server.
  \Drupal::moduleHandler()->alter('file_url', $uri);

  $scheme = StreamWrapperManager::getScheme($uri);

  if (!$scheme) {
    // Allow for:
    // - root-relative URIs (e.g. /foo.jpg in http://example.com/foo.jpg)
    // - protocol-relative URIs (e.g. //bar.jpg, which is expanded to
    //   http://example.com/bar.jpg by the browser when viewing a page over
    //   HTTP and to https://example.com/bar.jpg when viewing a HTTPS page)
    // Both types of relative URIs are characterized by a leading slash, hence
    // we can use a single check.
    if (mb_substr($uri, 0, 1) == '/') {
      return $uri;
    }
    else {
      // If this is not a properly formatted stream, then it is a shipped file.
      // Therefore, return the urlencoded URI with the base URL prepended.
      $options = UrlHelper::parse($uri);
      $path = $GLOBALS['base_url'] . '/' . UrlHelper::encodePath($options['path']);
      // Append the query.
      if ($options['query']) {
        $path .= '?' . UrlHelper::buildQuery($options['query']);
      }

      // Append fragment.
      if ($options['fragment']) {
        $path .= '#' . $options['fragment'];
      }

      return $path;
    }
  }
  elseif ($scheme == 'http' || $scheme == 'https' || $scheme == 'data') {
    // Check for HTTP and data URI-encoded URLs so that we don't have to
    // implement getExternalUrl() for the HTTP and data schemes.
    return $uri;
  }
  else {
    // Attempt to return an external URL using the appropriate wrapper.
    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($uri)) {
      return $wrapper->getExternalUrl();
    }
    else {
      return FALSE;
    }
  }
}

/**
 * Transforms an absolute URL of a local file to a relative URL.
 *
 * May be useful to prevent problems on multisite set-ups and prevent mixed
 * content errors when using HTTPS + HTTP.
 *
 * @param string $file_url
 *   A file URL of a local file as generated by file_create_url().
 *
 * @return string
 *   If the file URL indeed pointed to a local file and was indeed absolute,
 *   then the transformed, relative URL to the local file. Otherwise: the
 *   original value of $file_url.
 *
 * @see file_create_url()
 */
function file_url_transform_relative($file_url) {
  // Unfortunately, we pretty much have to duplicate Symfony's
  // Request::getHttpHost() method because Request::getPort() may return NULL
  // instead of a port number.
  $request = \Drupal::request();
  $host = $request->getHost();
  $scheme = $request->getScheme();
  $port = $request->getPort() ?: 80;
  if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
    $http_host = $host;
  }
  else {
    $http_host = $host . ':' . $port;
  }

  return preg_replace('|^https?://' . preg_quote($http_host, '|') . '|', '', $file_url);
}

/**
 * Checks that the directory exists and is writable.
 *
 * Directories need to have execute permissions to be considered a directory by
 * FTP servers, etc.
 *
 * @param $directory
 *   A string reference containing the name of a directory path or URI. A
 *   trailing slash will be trimmed from a path.
 * @param $options
 *   A bitmask to indicate if the directory should be created if it does
 *   not exist (FILE_CREATE_DIRECTORY) or made writable if it is read-only
 *   (FILE_MODIFY_PERMISSIONS).
 *
 * @return
 *   TRUE if the directory exists (or was created) and is writable. FALSE
 *   otherwise.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::prepareDirectory().
 */
function file_prepare_directory(&$directory, $options = FileSystemInterface::MODIFY_PERMISSIONS) {
  @trigger_error('file_prepare_directory() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::prepareDirectory(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->prepareDirectory($directory, $options);
}

/**
 * Creates a .htaccess file in each Drupal files directory if it is missing.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 * \Drupal\Core\File\HtaccessWriterInterface::ensure() instead.
 *
 * @see https://www.drupal.org/node/2940126
 */
function file_ensure_htaccess() {
  @trigger_error("file_ensure_htaccess() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\HtaccessWriter::ensure() instead. See https://www.drupal.org/node/2940126", E_USER_DEPRECATED);
  \Drupal::service('file.htaccess_writer')->ensure();
}

/**
 * Creates a .htaccess file in the given directory.
 *
 * @param string $directory
 *   The directory.
 * @param bool $private
 *   (Optional) FALSE indicates that $directory should be a web-accessible
 *   directory. Defaults to TRUE which indicates a private directory.
 * @param bool $force_overwrite
 *   (Optional) Set to TRUE to attempt to overwrite the existing .htaccess file
 *   if one is already present. Defaults to FALSE.
 *
 * @return bool
 *   TRUE when file exists or created successfully, FALSE otherwise.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Component\FileSecurity\FileSecurity::writeHtaccess() instead.
 *
 * @see https://www.drupal.org/node/2940126
 */
function file_save_htaccess($directory, $private = TRUE, $force_overwrite = FALSE) {
  @trigger_error('file_save_htaccess() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Component\FileSecurity\FileSecurity::writeHtaccess() instead. See https://www.drupal.org/node/2940126', E_USER_DEPRECATED);
  return \Drupal::service('file.htaccess_writer')->write($directory, $private, $force_overwrite);
}

/**
 * Returns the standard .htaccess lines that Drupal writes to file directories.
 *
 * @param bool $private
 *   (Optional) Set to FALSE to return the .htaccess lines for a web-accessible
 *   public directory. The default is TRUE, which returns the .htaccess lines
 *   for a private directory that should not be web-accessible.
 *
 * @return string
 *   The desired contents of the .htaccess file.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Component\FileSecurity\FileSecurity::htaccessLines().
 *
 * @see https://www.drupal.org/node/2418133
 */
function file_htaccess_lines($private = TRUE) {
  return FileSecurity::htaccessLines($private);
}

/**
 * Determines whether the URI has a valid scheme for file API operations.
 *
 * There must be a scheme and it must be a Drupal-provided scheme like
 * 'public', 'private', 'temporary', or an extension provided with
 * hook_stream_wrappers().
 *
 * @param $uri
 *   The URI to be tested.
 *
 * @return
 *   TRUE if the URI is allowed.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::isValidUri()
 *   instead.
 *
 * @see https://www.drupal.org/node/3035273
 */
function file_valid_uri($uri) {
  @trigger_error('file_valid_uri() is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::isValidUri() instead. See https://www.drupal.org/node/3035273', E_USER_DEPRECATED);
  return \Drupal::service('stream_wrapper_manager')->isValidUri($uri);
}

/**
 * Copies a file to a new location without database changes or hook invocation.
 *
 * This is a powerful function that in many ways performs like an advanced
 * version of copy().
 * - Checks if $source and $destination are valid and readable/writable.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 * - If the $source and $destination are equal, the behavior depends on the
 *   $replace parameter. FILE_EXISTS_REPLACE will error out. FILE_EXISTS_RENAME
 *   will rename the file until the $destination is unique.
 * - Works around a PHP bug where copy() does not properly support streams if
 *   safe_mode or open_basedir are enabled.
 *   @see https://bugs.php.net/bug.php?id=60456
 *
 * @param $source
 *   A string specifying the filepath or URI of the source file.
 * @param $destination
 *   A URI containing the destination that $source should be copied to. The
 *   URI may be a bare filepath (without a scheme). If this value is omitted,
 *   Drupal's default files scheme will be used, usually "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   The path to the new file, or FALSE in the event of an error.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::copy().
 *
 * @see file_copy()
 * @see https://www.drupal.org/node/3006851
 */
function file_unmanaged_copy($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  @trigger_error('file_unmanaged_copy() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::copy(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  try {
    $file_system = \Drupal::service('file_system');

    // Build a destination URI if necessary.
    if (!isset($destination)) {
      $destination = file_build_uri($file_system->basename($source));
    }
    return $file_system->copy($source, $destination, $replace);
  }
  catch (FileException $e) {
    return FALSE;
  }
}

/**
 * Internal function that prepares the destination for a file_unmanaged_copy or
 * file_unmanaged_move operation.
 *
 * - Checks if $source and $destination are valid and readable/writable.
 * - Checks that $source is not equal to $destination; if they are an error
 *   is reported.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 *
 * @param $source
 *   A string specifying the filepath or URI of the source file.
 * @param $destination
 *   A URI containing the destination that $source should be moved/copied to.
 *   The URI may be a bare filepath (without a scheme) and in that case the
 *   default scheme (file://) will be used. If this value is omitted, Drupal's
 *   default files scheme will be used, usually "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   TRUE, or FALSE in the event of an error.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::getDestinationFilename() instead.
 *
 * @see file_unmanaged_copy()
 * @see file_unmanaged_move()
 * @see https://www.drupal.org/node/3006851
 */
function file_unmanaged_prepare($source, &$destination = NULL, $replace = FILE_EXISTS_RENAME) {
  @trigger_error('file_unmanaged_prepare() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::getDestinationFilename() instead. See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  $original_source = $source;
  $logger = \Drupal::logger('file');
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');

  // Assert that the source file actually exists.
  if (!file_exists($source)) {
    // @todo Replace \Drupal::messenger()->addError() calls with exceptions
    // instead.
    \Drupal::messenger()->addError(t('The specified file %file could not be moved/copied because no file by that name exists. Please check that you supplied the correct filename.', ['%file' => $original_source]));
    if (($realpath = $file_system->realpath($original_source)) !== FALSE) {
      $logger->notice('File %file (%realpath) could not be moved/copied because it does not exist.', ['%file' => $original_source, '%realpath' => $realpath]);
    }
    else {
      $logger->notice('File %file could not be moved/copied because it does not exist.', ['%file' => $original_source]);
    }
    return FALSE;
  }

  // Build a destination URI if necessary.
  if (!isset($destination)) {
    $destination = file_build_uri($file_system->basename($source));
  }

  // Prepare the destination directory.
  if (file_prepare_directory($destination)) {
    // The destination is already a directory, so append the source basename.
    $destination = file_stream_wrapper_uri_normalize($destination . '/' . $file_system->basename($source));
  }
  else {
    // Perhaps $destination is a dir/file?
    $dirname = $file_system->dirname($destination);
    if (!file_prepare_directory($dirname)) {
      // The destination is not valid.
      $logger->notice('File %file could not be moved/copied because the destination directory %destination is not configured correctly.', ['%file' => $original_source, '%destination' => $dirname]);
      \Drupal::messenger()->addError(t('The specified file %file could not be moved/copied because the destination directory is not properly configured. This may be caused by a problem with file or directory permissions. More information is available in the system log.', ['%file' => $original_source]));
      return FALSE;
    }
  }

  // Determine whether we can perform this operation based on overwrite rules.
  $destination = file_destination($destination, $replace);
  if ($destination === FALSE) {
    \Drupal::messenger()->addError(t('The file %file could not be moved/copied because a file by that name already exists in the destination directory.', ['%file' => $original_source]));
    $logger->notice('File %file could not be moved/copied because a file by that name already exists in the destination directory (%destination)', ['%file' => $original_source, '%destination' => $destination]);
    return FALSE;
  }

  // Assert that the source and destination filenames are not the same.
  $real_source = $file_system->realpath($source);
  $real_destination = $file_system->realpath($destination);
  if ($source == $destination || ($real_source !== FALSE) && ($real_source == $real_destination)) {
    \Drupal::messenger()->addError(t('The specified file %file was not moved/copied because it would overwrite itself.', ['%file' => $source]));
    $logger->notice('File %file could not be moved/copied because it would overwrite itself.', ['%file' => $source]);
    return FALSE;
  }
  // Make sure the .htaccess files are present.
  file_ensure_htaccess();
  return TRUE;
}

/**
 * Constructs a URI to Drupal's default files location given a relative path.
 */
function file_build_uri($path) {
  $uri = \Drupal::config('system.file')->get('default_scheme') . '://' . $path;
  /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
  $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
  return $stream_wrapper_manager->normalizeUri($uri);
}

/**
 * Determines the destination path for a file.
 *
 * @param $destination
 *   A string specifying the desired final URI or filepath.
 * @param $replace
 *   Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   The destination filepath, or FALSE if the file already exists
 *   and FILE_EXISTS_ERROR is specified.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::getDestinationFilename().
 *
 * @see https://www.drupal.org/node/3006851
 */
function file_destination($destination, $replace) {
  @trigger_error('file_destination() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::getDestinationFilename(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->getDestinationFilename($destination, $replace);
}

/**
 * Moves a file to a new location without database changes or hook invocation.
 *
 * This is a powerful function that in many ways performs like an advanced
 * version of rename().
 * - Checks if $source and $destination are valid and readable/writable.
 * - Checks that $source is not equal to $destination; if they are an error
 *   is reported.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 * - Works around a PHP bug where rename() does not properly support streams if
 *   safe_mode or open_basedir are enabled.
 *   @see https://bugs.php.net/bug.php?id=60456
 *
 * @param $source
 *   A string specifying the filepath or URI of the source file.
 * @param $destination
 *   A URI containing the destination that $source should be moved to. The
 *   URI may be a bare filepath (without a scheme) and in that case the default
 *   scheme (file://) will be used. If this value is omitted, Drupal's default
 *   files scheme will be used, usually "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   The path to the new file, or FALSE in the event of an error.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::move().
 *
 * @see file_move()
 * @see https://www.drupal.org/node/3006851
 */
function file_unmanaged_move($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  @trigger_error('file_unmanaged_move() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::move(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  try {
    $file_system = \Drupal::service('file_system');

    // Build a destination URI if necessary.
    if (!isset($destination)) {
      $destination = file_build_uri($file_system->basename($source));
    }
    return $file_system->move($source, $destination, $replace);
  }
  catch (FileException $e) {
    return FALSE;
  }
}

/**
 * Modifies a filename as needed for security purposes.
 *
 * Munging a file name prevents unknown file extensions from masking exploit
 * files. When web servers such as Apache decide how to process a URL request,
 * they use the file extension. If the extension is not recognized, Apache
 * skips that extension and uses the previous file extension. For example, if
 * the file being requested is exploit.php.pps, and Apache does not recognize
 * the '.pps' extension, it treats the file as PHP and executes it. To make
 * this file name safe for Apache and prevent it from executing as PHP, the
 * .php extension is "munged" into .php_, making the safe file name
 * exploit.php_.pps.
 *
 * Specifically, this function adds an underscore to all extensions that are
 * between 2 and 5 characters in length, internal to the file name, and not
 * included in $extensions.
 *
 * Function behavior is also controlled by the configuration
 * 'system.file:allow_insecure_uploads'. If it evaluates to TRUE, no alterations
 * will be made, if it evaluates to FALSE, the filename is 'munged'. *
 * @param $filename
 *   File name to modify.
 * @param $extensions
 *   A space-separated list of extensions that should not be altered.
 * @param $alerts
 *   If TRUE, \Drupal::messenger()->addStatus() will be called to display
 *   a message if the file name was changed.
 *
 * @return string
 *   The potentially modified $filename.
 */
function file_munge_filename($filename, $extensions, $alerts = TRUE) {
  $original = $filename;

  // Allow potentially insecure uploads for very savvy users and admin
  if (!\Drupal::config('system.file')->get('allow_insecure_uploads')) {
    // Remove any null bytes. See
    // http://php.net/manual/security.filesystem.nullbytes.php
    $filename = str_replace(chr(0), '', $filename);

    $allowed_extensions = array_unique(explode(' ', strtolower(trim($extensions))));

    // Split the filename up by periods. The first part becomes the basename
    // the last part the final extension.
    $filename_parts = explode('.', $filename);
    // Remove file basename.
    $new_filename = array_shift($filename_parts);
    // Remove final extension.
    $final_extension = array_pop($filename_parts);

    // Loop through the middle parts of the name and add an underscore to the
    // end of each section that could be a file extension but isn't in the list
    // of allowed extensions.
    foreach ($filename_parts as $filename_part) {
      $new_filename .= '.' . $filename_part;
      if (!in_array(strtolower($filename_part), $allowed_extensions) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
        $new_filename .= '_';
      }
    }
    $filename = $new_filename . '.' . $final_extension;

    if ($alerts && $original != $filename) {
      \Drupal::messenger()->addStatus(t('For security reasons, your upload has been renamed to %filename.', ['%filename' => $filename]));
    }
  }

  return $filename;
}

/**
 * Undoes the effect of file_munge_filename().
 *
 * @param $filename
 *   String with the filename to be unmunged.
 *
 * @return
 *   An unmunged filename string.
 */
function file_unmunge_filename($filename) {
  return str_replace('_.', '.', $filename);
}

/**
 * Creates a full file path from a directory and filename.
 *
 * If a file with the specified name already exists, an alternative will be
 * used.
 *
 * @param $basename
 *   String filename
 * @param $directory
 *   String containing the directory or parent URI.
 *
 * @return
 *   File path consisting of $directory and a unique filename based off
 *   of $basename.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::createFilename().
 *
 * @see https://www.drupal.org/node/3006851
 */
function file_create_filename($basename, $directory) {
  @trigger_error('file_create_filename() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::createFilename(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->createFilename($basename, $directory);
}

/**
 * Deletes a file and its database record.
 *
 * Instead of directly deleting a file, it is strongly recommended to delete
 * file usages instead. That will automatically mark the file as temporary and
 * remove it during cleanup.
 *
 * @param $fid
 *   The file id.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Entity\EntityStorageInterface::delete() instead.
 *
 * @see file_unmanaged_delete()
 * @see \Drupal\file\FileUsage\FileUsageBase::delete()
 * @see \Drupal\Core\Entity\EntityStorageInterface::delete()
 * @see https://www.drupal.org/node/3021663
 */
function file_delete($fid) {
  @trigger_error('file_delete() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityStorageInterface::delete() instead. See https://www.drupal.org/node/3021663.', E_USER_DEPRECATED);
  return file_delete_multiple([$fid]);
}

/**
 * Deletes files.
 *
 * Instead of directly deleting a file, it is strongly recommended to delete
 * file usages instead. That will automatically mark the file as temporary and
 * remove it during cleanup.
 *
 * @param $fids
 *   An array of file ids.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Entity\EntityStorageInterface::delete() instead.
 *
 * @see file_unmanaged_delete()
 * @see \Drupal\file\FileUsage\FileUsageBase::delete()
 * @see \Drupal\Core\Entity\EntityStorageInterface::delete()
 * @see https://www.drupal.org/node/3021663
 */
function file_delete_multiple(array $fids) {
  @trigger_error('file_delete_multiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityStorageInterface::delete() instead. See https://www.drupal.org/node/3021663.', E_USER_DEPRECATED);
  $storage = \Drupal::entityTypeManager()->getStorage('file');
  $entities = $storage->loadMultiple($fids);
  $storage->delete($entities);
}

/**
 * Deletes a file without database changes or hook invocations.
 *
 * This function should be used when the file to be deleted does not have an
 * entry recorded in the files table.
 *
 * @param $path
 *   A string containing a file path or (streamwrapper) URI.
 *
 * @return
 *   TRUE for success or path does not exist, or FALSE in the event of an
 *   error.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::delete().
 *
 * @see file_delete()
 * @see file_unmanaged_delete_recursive()
 * @see https://www.drupal.org/node/3006851
 */
function file_unmanaged_delete($path) {
  @trigger_error('file_unmanaged_delete() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::delete(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  try {
    return \Drupal::service('file_system')->delete($path);
  }
  catch (FileException $e) {
    return FALSE;
  }
}

/**
 * Deletes all files and directories in the specified filepath recursively.
 *
 * If the specified path is a directory then the function will call itself
 * recursively to process the contents. Once the contents have been removed the
 * directory will also be removed.
 *
 * If the specified path is a file then it will be passed to
 * file_unmanaged_delete().
 *
 * Note that this only deletes visible files with write permission.
 *
 * @param $path
 *   A string containing either an URI or a file or directory path.
 * @param callable $callback
 *   (optional) Callback function to run on each file prior to deleting it and
 *   on each directory prior to traversing it. For example, can be used to
 *   modify permissions.
 *
 * @return
 *   TRUE for success or if path does not exist, FALSE in the event of an
 *   error.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::deleteRecursive().
 *
 * @see file_unmanaged_delete()
 * @see https://www.drupal.org/node/3006851
 */
function file_unmanaged_delete_recursive($path, $callback = NULL) {
  @trigger_error('file_unmanaged_delete_recursive() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::deleteRecursive(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  $callback = is_callable($callback) ? $callback : NULL;
  try {
    return \Drupal::service('file_system')->deleteRecursive($path, $callback);
  }
  catch (FileException $e) {
    return FALSE;
  }
}

/**
 * Moves an uploaded file to a new location.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::moveUploadedFile().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_move_uploaded_file($filename, $uri) {
  @trigger_error('drupal_move_uploaded_file() is deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::moveUploadedFile(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->moveUploadedFile($filename, $uri);
}

/**
 * Saves a file to the specified destination without invoking file API.
 *
 * This function is identical to file_save_data() except the file will not be
 * saved to the {file_managed} table and none of the file_* hooks will be
 * called.
 *
 * @param $data
 *   A string containing the contents of the file.
 * @param $destination
 *   A string containing the destination location. This must be a stream wrapper
 *   URI. If no value is provided, a randomized name will be generated and the
 *   file will be saved using Drupal's default files scheme, usually
 *   "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *                          unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   A string with the path of the resulting file, or FALSE on error.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::saveData().
 *
 * @see file_save_data()
 * @see https://www.drupal.org/node/3006851
 */
function file_unmanaged_save_data($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  @trigger_error('file_unmanaged_save_data() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::saveData(). See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
  try {
    // Build a destination URI if necessary.
    if (!isset($destination)) {
      $destination = file_default_scheme() . '://';
    }
    return \Drupal::service('file_system')->saveData($data, $destination, $replace);
  }
  catch (FileWriteException $e) {
    \Drupal::messenger()->addError(t('The file could not be created.'));
    return FALSE;
  }
  catch (FileException $e) {
    return FALSE;
  }
}

/**
 * Finds all files that match a given mask in a given directory.
 *
 * Directories and files beginning with a dot are excluded; this prevents
 * hidden files and directories (such as SVN working directories) from being
 * scanned. Use the umask option to skip configuration directories to
 * eliminate the possibility of accidentally exposing configuration
 * information. Also, you can use the base directory, recurse, and min_depth
 * options to improve performance by limiting how much of the filesystem has
 * to be traversed.
 *
 * @param $dir
 *   The base directory or URI to scan, without trailing slash.
 * @param $mask
 *   The preg_match() regular expression for files to be included.
 * @param $options
 *   An associative array of additional options, with the following elements:
 *   - 'nomask': The preg_match() regular expression for files to be excluded.
 *     Defaults to the 'file_scan_ignore_directories' setting.
 *   - 'callback': The callback function to call for each match. There is no
 *     default callback.
 *   - 'recurse': When TRUE, the directory scan will recurse the entire tree
 *     starting at the provided directory. Defaults to TRUE.
 *   - 'key': The key to be used for the returned associative array of files.
 *     Possible values are 'uri', for the file's URI; 'filename', for the
 *     basename of the file; and 'name' for the name of the file without the
 *     extension. Defaults to 'uri'.
 *   - 'min_depth': Minimum depth of directories to return files from. Defaults
 *     to 0.
 * @param $depth
 *   The current depth of recursion. This parameter is only used internally and
 *   should not be passed in.
 *
 * @return
 *   An associative array (keyed on the chosen key) of objects with 'uri',
 *   'filename', and 'name' properties corresponding to the matched files.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystemInterface::scanDirectory() instead.
 *
 * @see https://www.drupal.org/node/3038437
 */
function file_scan_directory($dir, $mask, $options = [], $depth = 0) {
  @trigger_error('file_scan_directory() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::scanDirectory() instead. See https://www.drupal.org/node/3038437', E_USER_DEPRECATED);
  $files = [];
  try {
    if (is_dir($dir)) {
      $files = \Drupal::service('file_system')->scanDirectory($dir, $mask, $options);
    }
  }
  catch (FileException $e) {
    // Ignore and return empty array for BC.
  }
  return $files;
}

/**
 * Determines the maximum file upload size by querying the PHP settings.
 *
 * @return
 *   A file size limit in bytes based on the PHP upload_max_filesize and
 *   post_max_size
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Component\Utility\Environment::getUploadMaxSize() instead.
 */
function file_upload_max_size() {
  @trigger_error('file_upload_max_size() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Component\Utility\Environment::getUploadMaxSize() instead. See https://www.drupal.org/node/3000058.', E_USER_DEPRECATED);
  return Environment::getUploadMaxSize();
}

/**
 * Sets the permissions on a file or directory.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::chmod().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_chmod($uri, $mode = NULL) {
  @trigger_error('drupal_chmod() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::chmod(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->chmod($uri, $mode);
}

/**
 * Deletes a file.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::unlink().
 *
 * @see \Drupal\Core\File\FileSystem::unlink()
 * @see https://www.drupal.org/node/2418133
 */
function drupal_unlink($uri, $context = NULL) {
  @trigger_error('drupal_unlink() is deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::unlink(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->unlink($uri, $context);
}

/**
 * Resolves the absolute filepath of a local URI or filepath.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::realpath().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_realpath($uri) {
  @trigger_error('drupal_realpath() is deprecated in drupal:8.0.0 and will be removed in drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::realpath(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->realpath($uri);
}

/**
 * Gets the name of the directory from a given path.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::dirname().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_dirname($uri) {
  @trigger_error('drupal_dirname() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::dirname(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->dirname($uri);
}

/**
 * Gets the filename from a given path.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::basename().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_basename($uri, $suffix = NULL) {
  @trigger_error('drupal_basename() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::basename(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->basename($uri, $suffix);
}

/**
 * Creates a directory, optionally creating missing components in the path to
 * the directory.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::mkdir().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
  @trigger_error('drupal_mkdir() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::mkdir(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->mkdir($uri, $mode, $recursive, $context);
}

/**
 * Removes a directory.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::rmdir().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_rmdir($uri, $context = NULL) {
  @trigger_error('drupal_rmdir() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::rmdir(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->rmdir($uri, $context);
}

/**
 * Creates a file with a unique filename in the specified directory.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\File\FileSystem::tempnam().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_tempnam($directory, $prefix) {
  @trigger_error('tempnam() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::tempnam(). See https://www.drupal.org/node/2418133.', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->tempnam($directory, $prefix);
}

/**
 * Gets and sets the path of the configured temporary directory.
 *
 * @return mixed|null
 *   A string containing the path to the temporary directory.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\File\FileSystemInterface::getTempDirectory() instead.
 *
 * @see \Drupal\Core\File\FileSystemInterface::getTempDirectory()
 * @see https://www.drupal.org/node/3039255
 */
function file_directory_temp() {
  @trigger_error('file_directory_temp() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::getTempDirectory() instead. See https://www.drupal.org/node/3039255', E_USER_DEPRECATED);
  return \Drupal::service('file_system')->getTempDirectory();
}

/**
 * Discovers a writable system-appropriate temporary directory.
 *
 * @return mixed
 *   A string containing the path to the temporary directory.
 *
 * @deprecated in drupal:8.3.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory().
 *
 * @see https://www.drupal.org/node/2418133
 */
function file_directory_os_temp() {
  @trigger_error('file_directory_os_temp() is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory() instead. See https://www.drupal.org/node/2418133', E_USER_DEPRECATED);
  return ComponentFileSystem::getOsTemporaryDirectory();
}

/**
 * @} End of "defgroup file".
 */
