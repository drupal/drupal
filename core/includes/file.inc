<?php

/**
 * @file
 * API for handling file uploads and server file management.
 */

use Drupal\Component\FileSystem\FileSystem as ComponentFileSystem;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Component\Utility\Bytes;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\PrivateStream;

/**
 * Default mode for new directories. See drupal_chmod().
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::CHMOD_DIRECTORY.
 *
 * @see https://www.drupal.org/node/2418133
 */
const FILE_CHMOD_DIRECTORY = FileSystem::CHMOD_DIRECTORY;

/**
 * Default mode for new files. See drupal_chmod().
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::CHMOD_FILE.
 *
 * @see https://www.drupal.org/node/2418133
 */
const FILE_CHMOD_FILE = FileSystem::CHMOD_FILE;

/**
 * @defgroup file File interface
 * @{
 * Common file handling functions.
 */

/**
 * Flag used by file_prepare_directory() -- create directory if not present.
 */
const FILE_CREATE_DIRECTORY = 1;

/**
 * Flag used by file_prepare_directory() -- file permissions may be changed.
 */
const FILE_MODIFY_PERMISSIONS = 2;

/**
 * Flag for dealing with existing files: Appends number until name is unique.
 */
const FILE_EXISTS_RENAME = 0;

/**
 * Flag for dealing with existing files: Replace the existing file.
 */
const FILE_EXISTS_REPLACE = 1;

/**
 * Flag for dealing with existing files: Do nothing and return FALSE.
 */
const FILE_EXISTS_ERROR = 2;

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
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::uriScheme().
 *
 * @see https://www.drupal.org/node/2418133
 */
function file_uri_scheme($uri) {
  return \Drupal::service('file_system')->uriScheme($uri);
}

/**
 * Checks that the scheme of a stream URI is valid.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::validScheme().
 *
 * @see https://www.drupal.org/node/2418133
 */
function file_stream_wrapper_valid_scheme($scheme) {
  return \Drupal::service('file_system')->validScheme($scheme);
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
 * @see file_uri_scheme()
 */
function file_uri_target($uri) {
  // Remove the scheme from the URI and remove erroneous leading or trailing,
  // forward-slashes and backslashes.
  $target = trim(preg_replace('/^[\w\-]+:\/\/|^data:/', '', $uri), '\/');

  // If nothing was replaced, the URI doesn't have a valid scheme.
  return $target !== $uri ? $target : FALSE;
}

/**
 * Gets the default file stream implementation.
 *
 * @return string
 *   'public', 'private' or any other file scheme defined as the default.
 */
function file_default_scheme() {
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
 */
function file_stream_wrapper_uri_normalize($uri) {
  $scheme = \Drupal::service('file_system')->uriScheme($uri);

  if (file_stream_wrapper_valid_scheme($scheme)) {
    $target = file_uri_target($uri);

    if ($target !== FALSE) {
      $uri = $scheme . '://' . $target;
    }
  }

  return $uri;
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

  $scheme = \Drupal::service('file_system')->uriScheme($uri);

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
 */
function file_prepare_directory(&$directory, $options = FILE_MODIFY_PERMISSIONS) {
  if (!file_stream_wrapper_valid_scheme(\Drupal::service('file_system')->uriScheme($directory))) {
    // Only trim if we're not dealing with a stream.
    $directory = rtrim($directory, '/\\');
  }

  // Check if directory exists.
  if (!is_dir($directory)) {
    // Let mkdir() recursively create directories and use the default directory
    // permissions.
    if ($options & FILE_CREATE_DIRECTORY) {
      return @drupal_mkdir($directory, NULL, TRUE);
    }
    return FALSE;
  }
  // The directory exists, so check to see if it is writable.
  $writable = is_writable($directory);
  if (!$writable && ($options & FILE_MODIFY_PERMISSIONS)) {
    return drupal_chmod($directory);
  }

  return $writable;
}

/**
 * Creates a .htaccess file in each Drupal files directory if it is missing.
 */
function file_ensure_htaccess() {
  file_save_htaccess('public://', FALSE);
  $private_path = PrivateStream::basePath();
  if (!empty($private_path)) {
    file_save_htaccess('private://', TRUE);
  }
  file_save_htaccess('temporary://', TRUE);

  // If a staging directory exists then it should contain a .htaccess file.
  // @todo https://www.drupal.org/node/2696103 catch a more specific exception
  //   and simplify this code.
  try {
    $staging = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
  }
  catch (\Exception $e) {
    $staging = FALSE;
  }
  if ($staging) {
    // Note that we log an error here if we can't write the .htaccess file. This
    // can occur if the staging directory is read-only. If it is then it is the
    // user's responsibility to create the .htaccess file.
    file_save_htaccess($staging, TRUE);
  }
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
 */
function file_save_htaccess($directory, $private = TRUE, $force_overwrite = FALSE) {
  if (\Drupal::service('file_system')->uriScheme($directory)) {
    $htaccess_path = file_stream_wrapper_uri_normalize($directory . '/.htaccess');
  }
  else {
    $directory = rtrim($directory, '/\\');
    $htaccess_path = $directory . '/.htaccess';
  }

  if (file_exists($htaccess_path) && !$force_overwrite) {
    // Short circuit if the .htaccess file already exists.
    return TRUE;
  }
  $htaccess_lines = FileStorage::htaccessLines($private);

  // Write the .htaccess file.
  if (file_exists($directory) && is_writable($directory) && file_put_contents($htaccess_path, $htaccess_lines)) {
    return drupal_chmod($htaccess_path, 0444);
  }
  else {
    $variables = ['%directory' => $directory, '@htaccess' => $htaccess_lines];
    \Drupal::logger('security')->error("Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <pre><code>@htaccess</code></pre>", $variables);
    return FALSE;
  }
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
 * @deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Component\PhpStorage\FileStorage::htaccessLines().
 *
 * @see https://www.drupal.org/node/2418133
 */
function file_htaccess_lines($private = TRUE) {
  return FileStorage::htaccessLines($private);
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
 */
function file_valid_uri($uri) {
  // Assert that the URI has an allowed scheme. Bare paths are not allowed.
  $uri_scheme = \Drupal::service('file_system')->uriScheme($uri);
  if (!file_stream_wrapper_valid_scheme($uri_scheme)) {
    return FALSE;
  }
  return TRUE;
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
 * @see file_copy()
 */
function file_unmanaged_copy($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  if (!file_unmanaged_prepare($source, $destination, $replace)) {
    return FALSE;
  }
  // Attempt to resolve the URIs. This is necessary in certain configurations
  // (see above).
  $file_system = \Drupal::service('file_system');
  $real_source = $file_system->realpath($source) ?: $source;
  $real_destination = $file_system->realpath($destination) ?: $destination;
  // Perform the copy operation.
  if (!@copy($real_source, $real_destination)) {
    \Drupal::logger('file')->error('The specified file %file could not be copied to %destination.', ['%file' => $source, '%destination' => $destination]);
    return FALSE;
  }
  // Set the permissions on the new file.
  drupal_chmod($destination);
  return $destination;
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
 * @see file_unmanaged_copy()
 * @see file_unmanaged_move()
 */
function file_unmanaged_prepare($source, &$destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $original_source = $source;
  $logger = \Drupal::logger('file');
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
    $destination = file_build_uri(drupal_basename($source));
  }

  // Prepare the destination directory.
  if (file_prepare_directory($destination)) {
    // The destination is already a directory, so append the source basename.
    $destination = file_stream_wrapper_uri_normalize($destination . '/' . drupal_basename($source));
  }
  else {
    // Perhaps $destination is a dir/file?
    $dirname = drupal_dirname($destination);
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
  $uri = file_default_scheme() . '://' . $path;
  return file_stream_wrapper_uri_normalize($uri);
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
 */
function file_destination($destination, $replace) {
  if (file_exists($destination)) {
    switch ($replace) {
      case FILE_EXISTS_REPLACE:
        // Do nothing here, we want to overwrite the existing file.
        break;

      case FILE_EXISTS_RENAME:
        $basename = drupal_basename($destination);
        $directory = drupal_dirname($destination);
        $destination = file_create_filename($basename, $directory);
        break;

      case FILE_EXISTS_ERROR:
        // Error reporting handled by calling function.
        return FALSE;
    }
  }
  return $destination;
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
 * @see file_move()
 */
function file_unmanaged_move($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  if (!file_unmanaged_prepare($source, $destination, $replace)) {
    return FALSE;
  }
  // Ensure compatibility with Windows.
  // @see drupal_unlink()
  if ((substr(PHP_OS, 0, 3) == 'WIN') && (!file_stream_wrapper_valid_scheme(file_uri_scheme($source)))) {
    chmod($source, 0600);
  }
  // Attempt to resolve the URIs. This is necessary in certain configurations
  // (see above) and can also permit fast moves across local schemes.
  $file_system = \Drupal::service('file_system');
  $real_source = $file_system->realpath($source) ?: $source;
  $real_destination = $file_system->realpath($destination) ?: $destination;
  // Perform the move operation.
  if (!@rename($real_source, $real_destination)) {
    // Fall back to slow copy and unlink procedure. This is necessary for
    // renames across schemes that are not local, or where rename() has not been
    // implemented. It's not necessary to use drupal_unlink() as the Windows
    // issue has already been resolved above.
    if (!@copy($real_source, $real_destination) || !@unlink($real_source)) {
      \Drupal::logger('file')->error('The specified file %file could not be moved to %destination.', ['%file' => $source, '%destination' => $destination]);
      return FALSE;
    }
  }
  // Set the permissions on the new file.
  drupal_chmod($destination);
  return $destination;
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

    $whitelist = array_unique(explode(' ', strtolower(trim($extensions))));

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
      if (!in_array(strtolower($filename_part), $whitelist) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
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
 */
function file_create_filename($basename, $directory) {
  // Strip control characters (ASCII value < 32). Though these are allowed in
  // some filesystems, not many applications handle them well.
  $basename = preg_replace('/[\x00-\x1F]/u', '_', $basename);
  if (substr(PHP_OS, 0, 3) == 'WIN') {
    // These characters are not allowed in Windows filenames
    $basename = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $basename);
  }

  // A URI or path may already have a trailing slash or look like "public://".
  if (substr($directory, -1) == '/') {
    $separator = '';
  }
  else {
    $separator = '/';
  }

  $destination = $directory . $separator . $basename;

  if (file_exists($destination)) {
    // Destination file already exists, generate an alternative.
    $pos = strrpos($basename, '.');
    if ($pos !== FALSE) {
      $name = substr($basename, 0, $pos);
      $ext = substr($basename, $pos);
    }
    else {
      $name = $basename;
      $ext = '';
    }

    $counter = 0;
    do {
      $destination = $directory . $separator . $name . '_' . $counter++ . $ext;
    } while (file_exists($destination));
  }

  return $destination;
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
 * @see file_unmanaged_delete()
 * @see \Drupal\file\FileUsage\FileUsageBase::delete()
 */
function file_delete($fid) {
  return file_delete_multiple([$fid]);
}

/**
 * Deletes files.
 *
 * Instead of directly deleting a file, it is strongly recommended to delete
 * file usages instead. That will automatically mark the file as temporary and
 * remove it during cleanup.
 *
 * @param $fid
 *   The file id.
 *
 * @see file_unmanaged_delete()
 * @see \Drupal\file\FileUsage\FileUsageBase::delete()
 */
function file_delete_multiple(array $fids) {
  entity_delete_multiple('file', $fids);
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
 * @see file_delete()
 * @see file_unmanaged_delete_recursive()
 */
function file_unmanaged_delete($path) {
  if (is_file($path)) {
    return drupal_unlink($path);
  }
  $logger = \Drupal::logger('file');
  if (is_dir($path)) {
    $logger->error('%path is a directory and cannot be removed using file_unmanaged_delete().', ['%path' => $path]);
    return FALSE;
  }
  // Return TRUE for non-existent file, but log that nothing was actually
  // deleted, as the current state is the intended result.
  if (!file_exists($path)) {
    $logger->notice('The file %path was not deleted because it does not exist.', ['%path' => $path]);
    return TRUE;
  }
  // We cannot handle anything other than files and directories. Log an error
  // for everything else (sockets, symbolic links, etc).
  $logger->error('The file %path is not of a recognized type so it was not deleted.', ['%path' => $path]);
  return FALSE;
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
 * @see file_unmanaged_delete()
 */
function file_unmanaged_delete_recursive($path, $callback = NULL) {
  if (isset($callback)) {
    call_user_func($callback, $path);
  }
  if (is_dir($path)) {
    $dir = dir($path);
    while (($entry = $dir->read()) !== FALSE) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      $entry_path = $path . '/' . $entry;
      file_unmanaged_delete_recursive($entry_path, $callback);
    }
    $dir->close();

    return drupal_rmdir($path);
  }
  return file_unmanaged_delete($path);
}

/**
 * Moves an uploaded file to a new location.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::moveUploadedFile().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_move_uploaded_file($filename, $uri) {
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
 * @see file_save_data()
 */
function file_unmanaged_save_data($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  // Write the data to a temporary file.
  $temp_name = drupal_tempnam('temporary://', 'file');
  if (file_put_contents($temp_name, $data) === FALSE) {
    \Drupal::messenger()->addError(t('The file could not be created.'));
    return FALSE;
  }

  // Move the file to its final destination.
  return file_unmanaged_move($temp_name, $destination, $replace);
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
 */
function file_scan_directory($dir, $mask, $options = [], $depth = 0) {
  // Merge in defaults.
  $options += [
    'callback' => 0,
    'recurse' => TRUE,
    'key' => 'uri',
    'min_depth' => 0,
  ];
  // Normalize $dir only once.
  if ($depth == 0) {
    $dir = file_stream_wrapper_uri_normalize($dir);
    $dir_has_slash = (substr($dir, -1) === '/');
  }

  // Allow directories specified in settings.php to be ignored. You can use this
  // to not check for files in common special-purpose directories. For example,
  // node_modules and bower_components. Ignoring irrelevant directories is a
  // performance boost.
  if (!isset($options['nomask'])) {
    $ignore_directories = Settings::get('file_scan_ignore_directories', []);
    array_walk($ignore_directories, function (&$value) {
      $value = preg_quote($value, '/');
    });
    $default_nomask = '/^' . implode('|', $ignore_directories) . '$/';
  }

  $options['key'] = in_array($options['key'], ['uri', 'filename', 'name']) ? $options['key'] : 'uri';
  $files = [];
  // Avoid warnings when opendir does not have the permissions to open a
  // directory.
  if (is_dir($dir)) {
    if ($handle = @opendir($dir)) {
      while (FALSE !== ($filename = readdir($handle))) {
        // Skip this file if it matches the nomask or starts with a dot.
        if ($filename[0] != '.'
          && !(isset($options['nomask']) && preg_match($options['nomask'], $filename))
          && !(!empty($default_nomask) && preg_match($default_nomask, $filename))
          ) {
          if ($depth == 0 && $dir_has_slash) {
            $uri = "$dir$filename";
          }
          else {
            $uri = "$dir/$filename";
          }
          if ($options['recurse'] && is_dir($uri)) {
            // Give priority to files in this folder by merging them in after
            // any subdirectory files.
            $files = array_merge(file_scan_directory($uri, $mask, $options, $depth + 1), $files);
          }
          elseif ($depth >= $options['min_depth'] && preg_match($mask, $filename)) {
            // Always use this match over anything already set in $files with
            // the same $options['key'].
            $file = new stdClass();
            $file->uri = $uri;
            $file->filename = $filename;
            $file->name = pathinfo($filename, PATHINFO_FILENAME);
            $key = $options['key'];
            $files[$file->$key] = $file;
            if ($options['callback']) {
              $options['callback']($uri);
            }
          }
        }
      }

      closedir($handle);
    }
    else {
      \Drupal::logger('file')->error('@dir can not be opened', ['@dir' => $dir]);
    }
  }

  return $files;
}

/**
 * Determines the maximum file upload size by querying the PHP settings.
 *
 * @return
 *   A file size limit in bytes based on the PHP upload_max_filesize and
 *   post_max_size
 */
function file_upload_max_size() {
  static $max_size = -1;

  if ($max_size < 0) {
    // Start with post_max_size.
    $max_size = Bytes::toInt(ini_get('post_max_size'));

    // If upload_max_size is less, then reduce. Except if upload_max_size is
    // zero, which indicates no limit.
    $upload_max = Bytes::toInt(ini_get('upload_max_filesize'));
    if ($upload_max > 0 && $upload_max < $max_size) {
      $max_size = $upload_max;
    }
  }
  return $max_size;
}

/**
 * Sets the permissions on a file or directory.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::chmod().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_chmod($uri, $mode = NULL) {
  return \Drupal::service('file_system')->chmod($uri, $mode);
}

/**
 * Deletes a file.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::unlink().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_unlink($uri, $context = NULL) {
  return \Drupal::service('file_system')->unlink($uri, $context);
}

/**
 * Resolves the absolute filepath of a local URI or filepath.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::realpath().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_realpath($uri) {
  return \Drupal::service('file_system')->realpath($uri);
}

/**
 * Gets the name of the directory from a given path.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::dirname().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_dirname($uri) {
  return \Drupal::service('file_system')->dirname($uri);
}

/**
 * Gets the filename from a given path.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::basename().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_basename($uri, $suffix = NULL) {
  return \Drupal::service('file_system')->basename($uri, $suffix);
}

/**
 * Creates a directory, optionally creating missing components in the path to
 * the directory.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::mkdir().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
  return \Drupal::service('file_system')->mkdir($uri, $mode, $recursive, $context);
}

/**
 * Removes a directory.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::rmdir().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_rmdir($uri, $context = NULL) {
  return \Drupal::service('file_system')->rmdir($uri, $context);
}

/**
 * Creates a file with a unique filename in the specified directory.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\File\FileSystem::tempnam().
 *
 * @see https://www.drupal.org/node/2418133
 */
function drupal_tempnam($directory, $prefix) {
  return \Drupal::service('file_system')->tempnam($directory, $prefix);
}

/**
 * Gets and sets the path of the configured temporary directory.
 *
 * @return mixed|null
 *   A string containing the path to the temporary directory.
 */
function file_directory_temp() {
  $temporary_directory = \Drupal::config('system.file')->get('path.temporary');
  if (empty($temporary_directory)) {
    // Needs set up.
    $config = \Drupal::configFactory()->getEditable('system.file');
    $temporary_directory = ComponentFileSystem::getOsTemporaryDirectory();

    if (empty($temporary_directory)) {
      // If no directory has been found default to 'files/tmp'.
      $temporary_directory = PublicStream::basePath() . '/tmp';

      // Windows accepts paths with either slash (/) or backslash (\), but will
      // not accept a path which contains both a slash and a backslash. Since
      // the 'file_public_path' variable may have either format, we sanitize
      // everything to use slash which is supported on all platforms.
      $temporary_directory = str_replace('\\', '/', $temporary_directory);
    }
    // Save the path of the discovered directory. Do not check config schema on
    // save.
    $config->set('path.temporary', (string) $temporary_directory)->save(TRUE);
  }

  return $temporary_directory;
}

/**
 * Discovers a writable system-appropriate temporary directory.
 *
 * @return mixed
 *   A string containing the path to the temporary directory.
 *
 * @deprecated in Drupal 8.3.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory().
 *
 * @see https://www.drupal.org/node/2418133
 */
function file_directory_os_temp() {
  return ComponentFileSystem::getOsTemporaryDirectory();
}

/**
 * @} End of "defgroup file".
 */
