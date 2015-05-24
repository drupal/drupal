<?php

/**
 * @file
 * Contains \Drupal\Core\File\FileSystemInterface.
 */

namespace Drupal\Core\File;

/**
 * Provides an interface for helpers that operate on files and stream wrappers.
 */
interface FileSystemInterface {

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
   *   TRUE for success, FALSE in the event of an error.
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
   *   Refer to http://php.net/manual/ref.stream.php
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
   * Creates a directory, optionally creating missing components in the path to
   * the directory.
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
   *   Refer to http://php.net/manual/ref.stream.php
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
   *   Refer to http://php.net/manual/ref.stream.php
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
   * Returns the scheme of a URI (e.g. a stream).
   *
   * @param string $uri
   *   A stream, referenced as "scheme://target" or "data:target".
   *
   * @return string|bool
   *   A string containing the name of the scheme, or FALSE if none. For
   *   example, the URI "public://example.txt" would return "public".
   *
   * @see file_uri_target()
   */
  public function uriScheme($uri);

  /**
   * Checks that the scheme of a stream URI is valid.
   *
   * Confirms that there is a registered stream handler for the provided scheme
   * and that it is callable. This is useful if you want to confirm a valid
   * scheme without creating a new instance of the registered handler.
   *
   * @param string $scheme
   *   A URI scheme, a stream is referenced as "scheme://target".
   *
   * @return bool
   *   Returns TRUE if the string is the name of a validated stream, or FALSE if
   *   the scheme does not have a registered handler.
   */
  public function validScheme($scheme);

}
