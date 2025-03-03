<?php

namespace Drupal\Core\File;

/**
 * Provides an interface for helpers that operate on files and stream wrappers.
 */
interface FileSystemInterface {

  /**
   * Flag for dealing with existing files: Appends number until name is unique.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use
   * \Drupal\Core\File\FileExists::Rename instead.
   *
   * @see https://www.drupal.org/node/3426517
   */
  const EXISTS_RENAME = 0;

  /**
   * Flag for dealing with existing files: Replace the existing file.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use
   * \Drupal\Core\File\FileExists::Replace instead.
   *
   * @see https://www.drupal.org/node/3426517
   */
  const EXISTS_REPLACE = 1;

  /**
   * Flag for dealing with existing files: Do nothing and return FALSE.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use
   *  \Drupal\Core\File\FileExists::Error instead.
   *
   * @see https://www.drupal.org/node/3426517
   */
  const EXISTS_ERROR = 2;

  /**
   * Flag used by ::prepareDirectory() -- create directory if not present.
   */
  const CREATE_DIRECTORY = 1;

  /**
   * Flag used by ::prepareDirectory() -- file permissions may be changed.
   */
  const MODIFY_PERMISSIONS = 2;

  /**
   * A list of insecure extensions.
   *
   * @see \Drupal\Core\File\FileSystemInterface::INSECURE_EXTENSION_REGEX
   */
  public const INSECURE_EXTENSIONS = ['phar', 'php', 'pl', 'py', 'cgi', 'asp', 'js', 'htaccess', 'phtml'];

  /**
   * The regex pattern used when checking for insecure file types.
   *
   * @see \Drupal\Core\File\FileSystemInterface::INSECURE_EXTENSIONS
   */
  public const INSECURE_EXTENSION_REGEX = '/\.(phar|php|pl|py|cgi|asp|js|htaccess|phtml)(\.|$)/i';

  /**
   * Moves an uploaded file to a new location.
   *
   * PHP's move_uploaded_file() does not properly support streams if
   * open_basedir is enabled, so this function fills that gap.
   *
   * Compatibility: normal paths and stream wrappers.
   *
   * @param string $filename
   *   The filename of the uploaded file.
   * @param string $uri
   *   A string containing the destination URI of the file.
   *
   * @return bool
   *   TRUE on success, or FALSE on failure.
   *
   * @see move_uploaded_file()
   * @see https://www.drupal.org/node/515192
   * @ingroup php_wrappers
   */
  public function moveUploadedFile($filename, $uri);

  /**
   * Sets the permissions on a file or directory.
   *
   * This function will use the file_chmod_directory and
   * file_chmod_file settings for the default modes for directories
   * and uploaded/generated files. By default these will give everyone read
   * access so that users accessing the files with a user account without the
   * webserver group (e.g. via FTP) can read these files, and give group write
   * permissions so webserver group members (e.g. a vhost account) can alter
   * files uploaded and owned by the webserver.
   *
   * PHP's chmod does not support stream wrappers so we use our wrapper
   * implementation which interfaces with chmod() by default. Contrib wrappers
   * may override this behavior in their implementations as needed.
   *
   * @param string $uri
   *   A string containing a URI file, or directory path.
   * @param int $mode
   *   Integer value for the permissions. Consult PHP chmod() documentation for
   *   more information.
   *
   * @return bool
   *   TRUE for success, FALSE in the event of an error. Note, it is the
   *   caller's to log an error if necessary.
   *
   * @ingroup php_wrappers
   */
  public function chmod($uri, $mode = NULL);

  /**
   * Deletes a file.
   *
   * PHP's unlink() is broken on Windows, as it can fail to remove a file when
   * it has a read-only flag set.
   *
   * @param string $uri
   *   A URI or pathname.
   * @param resource $context
   *   Refer to http://php.net/manual/ref.stream.php.
   *
   * @return bool
   *   Boolean TRUE on success, or FALSE on failure.
   *
   * @see unlink()
   * @ingroup php_wrappers
   */
  public function unlink($uri, $context = NULL);

  /**
   * Resolves the absolute filepath of a local URI or filepath.
   *
   * The use of this method is discouraged, because it does not work for
   * remote URIs. Except in rare cases, URIs should not be manually resolved.
   *
   * Only use this function if you know that the stream wrapper in the URI uses
   * the local file system, and you need to pass an absolute path to a function
   * that is incompatible with stream URIs.
   *
   * @param string $uri
   *   A stream wrapper URI or a filepath, possibly including one or more
   *   symbolic links.
   *
   * @return string|false
   *   The absolute local filepath (with no symbolic links) or FALSE on failure.
   *
   * @see \Drupal\Core\StreamWrapper\StreamWrapperInterface::realpath()
   * @see http://php.net/manual/function.realpath.php
   * @ingroup php_wrappers
   */
  public function realpath($uri);

  /**
   * Gets the name of the directory from a given path.
   *
   * PHP's dirname() does not properly pass streams, so this function fills that
   * gap. It is backwards compatible with normal paths and will use PHP's
   * dirname() as a fallback.
   *
   * Compatibility: normal paths and stream wrappers.
   *
   * @param string $uri
   *   A URI or path.
   *
   * @return string
   *   A string containing the directory name.
   *
   * @see dirname()
   * @see https://www.drupal.org/node/515192
   * @ingroup php_wrappers
   */
  public function dirname($uri);

  /**
   * Gets the filename from a given path.
   *
   * PHP's basename() does not properly support streams or filenames beginning
   * with a non-US-ASCII character.
   *
   * @see http://bugs.php.net/bug.php?id=37738
   * @see basename()
   *
   * @ingroup php_wrappers
   */
  public function basename($uri, $suffix = NULL);

  /**
   * Creates a directory, optionally creating missing components in the path.
   *
   * When PHP's mkdir() creates a directory, the requested mode is affected by
   * the process's umask. This function overrides the umask and sets the mode
   * explicitly for all directory components created.
   *
   * @param string $uri
   *   A URI or pathname.
   * @param int $mode
   *   Mode given to created directories. Defaults to the directory mode
   *   configured in the Drupal installation. It must have a leading zero.
   * @param bool $recursive
   *   Create directories recursively, defaults to FALSE. Cannot work with a
   *   mode which denies writing or execution to the owner of the process.
   * @param resource $context
   *   Refer to http://php.net/manual/ref.stream.php.
   *
   * @return bool
   *   Boolean TRUE on success, or FALSE on failure.
   *
   * @see mkdir()
   * @see https://www.drupal.org/node/515192
   * @ingroup php_wrappers
   *
   * @todo Update with open_basedir compatible recursion logic from
   *   \Drupal\Component\PhpStorage\FileStorage::ensureDirectory().
   */
  public function mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL);

  /**
   * Removes a directory.
   *
   * PHP's rmdir() is broken on Windows, as it can fail to remove a directory
   * when it has a read-only flag set.
   *
   * @param string $uri
   *   A URI or pathname.
   * @param resource $context
   *   Refer to http://php.net/manual/ref.stream.php.
   *
   * @return bool
   *   Boolean TRUE on success, or FALSE on failure.
   *
   * @see rmdir()
   * @ingroup php_wrappers
   */
  public function rmdir($uri, $context = NULL);

  /**
   * Creates a file with a unique filename in the specified directory.
   *
   * PHP's tempnam() does not return a URI like we want. This function will
   * return a URI if given a URI, or it will return a filepath if given a
   * filepath.
   *
   * Compatibility: normal paths and stream wrappers.
   *
   * @param string $directory
   *   The directory where the temporary filename will be created.
   * @param string $prefix
   *   The prefix of the generated temporary filename.
   *   Note: Windows uses only the first three characters of prefix.
   *
   * @return string|bool
   *   The new temporary filename, or FALSE on failure.
   *
   * @see tempnam()
   * @see https://www.drupal.org/node/515192
   * @ingroup php_wrappers
   */
  public function tempnam($directory, $prefix);

  /**
   * Copies a file to a new location without invoking the file API.
   *
   * This is a powerful function that in many ways performs like an advanced
   * version of copy().
   * - If $source and $destination are valid and readable/writable, then only
   *   perform the copy operation.
   * - If $source and $destination are equal then a FileException exception is
   *   thrown.
   * - If the $destination file already exists, the behavior depends on the
   *   $fileExists parameter as follows `FileExists::Error` will error out,
   *   `FileExists::Replace` will replace the existing file, and
   *   `FileExists::Rename` will assign a new unique name.
   * - Provides a fallback using realpaths if the move fails using stream
   *   wrappers. This can occur because PHP's copy() function does not properly
   *   support streams if open_basedir is enabled.
   *
   * Example:
   * @code
   * use Drupal\Core\File\FileExists;
   * use Drupal\Core\File\FileSystemInterface;
   * ...
   * $directory = 'public://example-dir';
   * $file_system = \Drupal::service('file_system');
   * $file_system->copy($filepath, $directory . '/' . basename($filepath), FileExists::Replace);
   * @endcode
   * In this example, file is copied from $filepath and is replaced at the
   * destination if exists.
   *
   * @param string $source
   *   A string specifying the filepath or URI of the source file.
   * @param string $destination
   *   A URI containing the destination that $source should be copied to. The
   *   URI may be a bare filepath (without a scheme).
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   The behavior when the destination file already exists.
   *
   * @return string
   *   The path to the new file.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   * @throws \ValueError
   *   Thrown if $fileExists is a legacy int and not a valid value.
   */
  public function copy($source, $destination, /* FileExists */$fileExists = FileExists::Rename);

  /**
   * Deletes a file without database changes or hook invocations.
   *
   * This function should be used when the file to be deleted does not have an
   * entry recorded in the files table.
   *
   * @param string $path
   *   A string containing a file path or (streamwrapper) URI.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   */
  public function delete($path);

  /**
   * Deletes all files and directories in the specified filepath recursively.
   *
   * If the specified path is a directory then the function is called
   * recursively to process the contents. Once the contents have been removed
   * the directory is also removed.
   *
   * If the specified path is a file then it will be processed with delete()
   * method.
   *
   * Note that this only deletes visible files with write permission.
   *
   * @param string $path
   *   A string containing either an URI or a file or directory path.
   * @param callable|null $callback
   *   Callback function to run on each file prior to deleting it and on each
   *   directory prior to traversing it. For example, can be used to modify
   *   permissions.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   */
  public function deleteRecursive($path, ?callable $callback = NULL);

  /**
   * Moves a file to a new location without database changes or hook invocation.
   *
   * This is a powerful function that in many ways performs like an advanced
   * version of rename().
   * - Checks if $source and $destination are valid and readable/writable.
   * - Checks that $source is not equal to $destination; if they are an error
   *   is reported.
   * - If file already exists in $destination either the call will error out,
   *   replace the file or rename the file based on the $fileExists parameter.
   * - Works around a PHP bug where rename() does not properly support streams
   *   if safe_mode or open_basedir are enabled.
   *
   * @param string $source
   *   A string specifying the filepath or URI of the source file.
   * @param string $destination
   *   A URI containing the destination that $source should be moved to. The
   *   URI may be a bare filepath (without a scheme) and in that case the
   *   default scheme (public://) will be used.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   *
   * @return string
   *   The path to the new file.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   * @throws \ValueError
   *   Thrown if $fileExists is a legacy int and not a valid value.
   *
   * @see \Drupal\Core\File\FileSystemInterface::createFilename()
   * @see https://bugs.php.net/bug.php?id=60456
   */
  public function move($source, $destination, /* FileExists */$fileExists = FileExists::Rename);

  /**
   * Saves a file to the specified destination without invoking file API.
   *
   * This function is identical to writeData() except the file will not be
   * saved to the {file_managed} table and none of the file_* hooks will be
   * called.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param string $destination
   *   A string containing the destination location. This must be a stream
   *   wrapper URI.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   *
   * @return string
   *   A string with the path of the resulting file, or FALSE on error.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   * @throws \ValueError
   *   Thrown if $fileExists is a legacy int and not a valid value.
   *
   * @see \Drupal\file\FileRepositoryInterface::writeData()
   */
  public function saveData($data, $destination, /* FileExists */$fileExists = FileExists::Rename);

  /**
   * Checks that the directory exists and is writable.
   *
   * Directories need to have execute permissions to be considered a directory
   * by FTP servers, etc.
   *
   * @param string $directory
   *   A string reference containing the name of a directory path or URI. A
   *   trailing slash will be trimmed from a path.
   * @param int $options
   *   A bitmask to indicate if the directory should be created if it does
   *   not exist (FileSystemInterface::CREATE_DIRECTORY) or made writable if it
   *   is read-only (FileSystemInterface::MODIFY_PERMISSIONS).
   *
   * @return bool
   *   TRUE if the directory exists (or was created) and is writable. FALSE
   *   otherwise.
   */
  public function prepareDirectory(&$directory, $options = self::MODIFY_PERMISSIONS);

  /**
   * Creates a full file path from a directory and filename.
   *
   * If a file with the specified name already exists, an alternative will be
   * used.
   *
   * @param string $basename
   *   The filename.
   * @param string $directory
   *   The directory or parent URI.
   *
   * @return string
   *   File path consisting of $directory and a unique filename based off
   *   of $basename.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   */
  public function createFilename($basename, $directory);

  /**
   * Determines the destination path for a file.
   *
   * @param string $destination
   *   The desired final URI or filepath.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   *
   * @return string|bool
   *   The destination filepath, or FALSE if the file already exists
   *   and FileExists::Error is specified.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Implementation may throw FileException or its subtype on failure.
   * @throws \ValueError
   *   Thrown if $fileExists is a legacy int and not a valid value.
   */
  public function getDestinationFilename($destination, /* FileExists */$fileExists);

  /**
   * Gets the path of the configured temporary directory.
   *
   * If the path is not set, it will fall back to the OS-specific default if
   * set, otherwise a directory under the public files directory. It will then
   * set this as the configured directory.
   *
   * @return string
   *   A string containing the path to the temporary directory.
   */
  public function getTempDirectory();

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
   * @param string $dir
   *   The base directory or URI to scan, without trailing slash.
   * @param string $mask
   *   The preg_match() regular expression for files to be included.
   * @param array $options
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
   *   - 'min_depth': Minimum depth of directories to return files from.
   *     Defaults to 0.
   *
   * @return array
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' properties corresponding to the matched files.
   *
   * @throws \Drupal\Core\File\Exception\NotRegularDirectoryException
   *   If the directory does not exist.
   */
  public function scanDirectory($dir, $mask, array $options = []);

}
