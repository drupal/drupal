<?php

namespace Drupal\Core\File;

use Drupal\Component\FileSystem\FileSystem as FileSystemComponent;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\Exception\NotRegularFileException;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Provides helpers to operate on files and stream wrappers.
 */
class FileSystem implements FileSystemInterface {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected array $deprecatedProperties = [
    'logger' => 'logger.channel.file',
  ];

  /**
   * Default mode for new directories. See self::chmod().
   */
  const CHMOD_DIRECTORY = 0775;

  /**
   * Default mode for new files. See self::chmod().
   */
  const CHMOD_FILE = 0664;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new FileSystem.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager, Settings $settings) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function moveUploadedFile($filename, $uri) {
    $result = @move_uploaded_file($filename, $uri);
    // PHP's move_uploaded_file() does not properly support streams if
    // open_basedir is enabled so if the move failed, try finding a real path
    // and retry the move operation.
    if (!$result) {
      if ($realpath = $this->realpath($uri)) {
        $result = move_uploaded_file($filename, $realpath);
      }
      else {
        $result = move_uploaded_file($filename, $uri);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function chmod($uri, $mode = NULL) {
    if (!isset($mode)) {
      if (is_dir($uri)) {
        $mode = $this->settings->get('file_chmod_directory', static::CHMOD_DIRECTORY);
      }
      else {
        $mode = $this->settings->get('file_chmod_file', static::CHMOD_FILE);
      }
    }

    return @chmod($uri, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($uri, $context = NULL) {
    if (!$this->streamWrapperManager->isValidUri($uri) && str_starts_with(PHP_OS, 'WIN')) {
      chmod($uri, 0600);
    }
    if ($context) {
      return unlink($uri, $context);
    }
    else {
      return unlink($uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function realpath($uri) {
    // If this URI is a stream, pass it off to the appropriate stream wrapper.
    // Otherwise, attempt PHP's realpath. This allows use of this method even
    // for unmanaged files outside of the stream wrapper interface.
    if ($wrapper = $this->streamWrapperManager->getViaUri($uri)) {
      return $wrapper->realpath();
    }

    return realpath($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri) {
    $scheme = StreamWrapperManager::getScheme($uri);

    if ($this->streamWrapperManager->isValidScheme($scheme)) {
      return $this->streamWrapperManager->getViaScheme($scheme)->dirname($uri);
    }
    else {
      return dirname($uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function basename($uri, $suffix = NULL) {
    $separators = '/';
    if (DIRECTORY_SEPARATOR != '/') {
      // For Windows OS add special separator.
      $separators .= DIRECTORY_SEPARATOR;
    }
    // Remove right-most slashes when $uri points to directory.
    $uri = rtrim($uri, $separators);
    // Returns the trailing part of the $uri starting after one of the directory
    // separators.
    $filename = preg_match('@[^' . preg_quote($separators, '@') . ']+$@', $uri, $matches) ? $matches[0] : '';
    // Cuts off a suffix from the filename.
    if ($suffix) {
      $filename = preg_replace('@' . preg_quote($suffix, '@') . '$@', '', $filename);
    }
    return $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
    if (!isset($mode)) {
      $mode = $this->settings->get('file_chmod_directory', static::CHMOD_DIRECTORY);
    }

    // If the URI has a scheme, don't override the umask - schemes can handle
    // this issue in their own implementation.
    if (StreamWrapperManager::getScheme($uri)) {
      return $this->mkdirCall($uri, $mode, $recursive, $context);
    }

    // If recursive, create each missing component of the parent directory
    // individually and set the mode explicitly to override the umask.
    if ($recursive) {
      // Ensure the path is using DIRECTORY_SEPARATOR, and trim off any trailing
      // slashes because they can throw off the loop when creating the parent
      // directories.
      $uri = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $uri), DIRECTORY_SEPARATOR);
      // Determine the components of the path.
      $components = explode(DIRECTORY_SEPARATOR, $uri);
      // If the filepath is absolute the first component will be empty as there
      // will be nothing before the first slash.
      if ($components[0] == '') {
        $recursive_path = DIRECTORY_SEPARATOR;
        // Get rid of the empty first component.
        array_shift($components);
      }
      else {
        $recursive_path = '';
      }
      // Don't handle the top-level directory in this loop.
      array_pop($components);
      // Create each component if necessary.
      foreach ($components as $component) {
        $recursive_path .= $component;

        if (!file_exists($recursive_path)) {
          $success = $this->mkdirCall($recursive_path, $mode, FALSE, $context);
          // If the operation failed, check again if the directory was created
          // by another process/server, only report a failure if not.
          if (!$success && !file_exists($recursive_path)) {
            return FALSE;
          }
          // Not necessary to use self::chmod() as there is no scheme.
          if (!chmod($recursive_path, $mode)) {
            return FALSE;
          }
        }

        $recursive_path .= DIRECTORY_SEPARATOR;
      }
    }

    // Do not check if the top-level directory already exists, as this condition
    // must cause this function to fail.
    if (!$this->mkdirCall($uri, $mode, FALSE, $context)) {
      return FALSE;
    }
    // Not necessary to use self::chmod() as there is no scheme.
    return chmod($uri, $mode);
  }

  /**
   * Ensures we don't pass a NULL as a context resource to mkdir().
   *
   * @see self::mkdir()
   */
  protected function mkdirCall($uri, $mode, $recursive, $context) {
    if (is_null($context)) {
      return mkdir($uri, $mode, $recursive);
    }
    else {
      return mkdir($uri, $mode, $recursive, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($uri, $context = NULL) {
    if (!$this->streamWrapperManager->isValidUri($uri) && str_starts_with(PHP_OS, 'WIN')) {
      chmod($uri, 0700);
    }
    if ($context) {
      return rmdir($uri, $context);
    }
    else {
      return rmdir($uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tempnam($directory, $prefix) {
    $scheme = StreamWrapperManager::getScheme($directory);

    if ($this->streamWrapperManager->isValidScheme($scheme)) {
      $wrapper = $this->streamWrapperManager->getViaScheme($scheme);

      if ($filename = tempnam($wrapper->getDirectoryPath(), $prefix)) {
        return $scheme . '://' . static::basename($filename);
      }
      else {
        return FALSE;
      }
    }
    else {
      // Handle as a normal tempnam() call.
      return tempnam($directory, $prefix);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function copy($source, $destination, /* FileExists */$fileExists = FileExists::Rename) {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    $this->prepareDestination($source, $destination, $fileExists);

    if (!@copy($source, $destination)) {
      // If the copy failed and realpaths exist, retry the operation using them
      // instead.
      $real_source = $this->realpath($source) ?: $source;
      $real_destination = $this->realpath($destination) ?: $destination;
      if ($real_source === FALSE || $real_destination === FALSE || !@copy($real_source, $real_destination)) {
        throw new FileWriteException("The specified file '$source' could not be copied to '$destination'.");
      }
    }

    // Set the permissions on the new file.
    $this->chmod($destination);

    return $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path) {
    if (is_link($path)) {
      // See https://bugs.php.net/52176.
      if (!($this->unlink($path) || '\\' !== \DIRECTORY_SEPARATOR || $this->rmdir($path)) && file_exists($path)) {
        throw new FileException("Failed to unlink symlink '$path'.");
      }
      return TRUE;
    }
    if (is_file($path)) {
      if (!$this->unlink($path)) {
        throw new FileException("Failed to unlink file '$path'.");
      }
      return TRUE;
    }

    if (is_dir($path)) {
      throw new NotRegularFileException("Cannot delete '$path' because it is a directory. Use deleteRecursive() instead.");
    }

    // Return TRUE for non-existent file as the current state is the intended
    // result.
    if (!file_exists($path)) {
      return TRUE;
    }

    // We cannot handle anything other than files and directories.
    // Throw an exception for everything else (sockets, symbolic links, etc).
    throw new NotRegularFileException("The file '$path' is not of a recognized type so it was not deleted.");
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRecursive($path, ?callable $callback = NULL) {
    // Ensure paths are local paths when a recursive delete is started.
    if ($this->streamWrapperManager->isValidUri($path)) {
      $path = $this->realpath($path);
    }

    if ($callback) {
      call_user_func($callback, $path);
    }

    // Allow broken links to be removed.
    if (!file_exists($path) && !is_link($path)) {
      return TRUE;
    }

    if (is_dir($path) && !is_link($path)) {
      $dir = dir($path);
      while (($entry = $dir->read()) !== FALSE) {
        if ($entry == '.' || $entry == '..') {
          continue;
        }
        $entry_path = $path . '/' . $entry;
        $this->deleteRecursive($entry_path, $callback);
      }
      $dir->close();

      return $this->rmdir($path);
    }

    return $this->delete($path);
  }

  /**
   * {@inheritdoc}
   */
  public function move($source, $destination, /* FileExists */$fileExists = FileExists::Rename) {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    $this->prepareDestination($source, $destination, $fileExists);

    // Ensure compatibility with Windows.
    // @see \Drupal\Core\File\FileSystemInterface::unlink().
    if (!$this->streamWrapperManager->isValidUri($source) && str_starts_with(PHP_OS, 'WIN')) {
      chmod($source, 0600);
    }
    // Attempt to resolve the URIs. This is necessary in certain
    // configurations (see above) and can also permit fast moves across local
    // schemes.
    $real_source = $this->realpath($source) ?: $source;
    $real_destination = $this->realpath($destination) ?: $destination;
    // Perform the move operation.
    if (!@rename($real_source, $real_destination)) {
      // Fall back to slow copy and unlink procedure. This is necessary for
      // renames across schemes that are not local, or where rename() has not
      // been implemented. It's not necessary to use FileSystem::unlink() as the
      // Windows issue has already been resolved above.
      if (!@copy($real_source, $real_destination)) {
        throw new FileWriteException("The specified file '$source' could not be moved to '$destination'.");
      }
      if (!@unlink($real_source)) {
        throw new FileException("The source file '$source' could not be unlinked after copying to '$destination'.");
      }
    }

    // Set the permissions on the new file.
    $this->chmod($destination);

    return $destination;
  }

  /**
   * Prepares the destination for a file copy or move operation.
   *
   * - Checks if $source and $destination are valid and readable/writable.
   * - Checks that $source is not equal to $destination; if they are an error
   *   is reported.
   * - If file already exists in $destination either the call will error out,
   *   replace the file or rename the file based on the $replace parameter.
   *
   * @param string $source
   *   A string specifying the filepath or URI of the source file.
   * @param string|null $destination
   *   A URI containing the destination that $source should be moved/copied to.
   *   The URI may be a bare filepath (without a scheme) and in that case the
   *   default scheme (file://) will be used.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   *
   * @throws \TypeError
   *   Thrown when the $fileExists parameter is not an enum or legacy int.
   *
   * @see \Drupal\Core\File\FileSystemInterface::copy()
   * @see \Drupal\Core\File\FileSystemInterface::move()
   */
  protected function prepareDestination($source, &$destination, /* FileExists */$fileExists) {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    $original_source = $source;

    if (!file_exists($source)) {
      if (($realpath = $this->realpath($original_source)) !== FALSE) {
        throw new FileNotExistsException("File '$original_source' ('$realpath') could not be copied because it does not exist.");
      }
      else {
        throw new FileNotExistsException("File '$original_source' could not be copied because it does not exist.");
      }
    }

    // Prepare the destination directory.
    if ($this->prepareDirectory($destination)) {
      // The destination is already a directory, so append the source basename.
      $destination = $this->streamWrapperManager->normalizeUri($destination . '/' . $this->basename($source));
    }
    else {
      // Perhaps $destination is a dir/file?
      $dirname = $this->dirname($destination);
      if (!$this->prepareDirectory($dirname)) {
        throw new DirectoryNotReadyException("The specified file '$original_source' could not be copied because the destination directory '$dirname' is not properly configured. This may be caused by a problem with file or directory permissions.");
      }
    }

    // Determine whether we can perform this operation based on overwrite rules.
    $destination = $this->getDestinationFilename($destination, $fileExists);
    if ($destination === FALSE) {
      throw new FileExistsException("File '$original_source' could not be copied because a file by that name already exists in the destination directory ('$destination').");
    }

    // Assert that the source and destination filenames are not the same.
    $real_source = $this->realpath($source);
    $real_destination = $this->realpath($destination);
    if ($source == $destination || ($real_source !== FALSE) && ($real_source == $real_destination)) {
      throw new FileException("File '$source' could not be copied because it would overwrite itself.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveData($data, $destination, /* FileExists */$fileExists = FileExists::Rename) {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    // Write the data to a temporary file.
    $temp_name = $this->tempnam('temporary://', 'file');
    if (file_put_contents($temp_name, $data) === FALSE) {
      throw new FileWriteException("Temporary file '$temp_name' could not be created.");
    }

    // Move the file to its final destination.
    return $this->move($temp_name, $destination, $fileExists);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDirectory(&$directory, $options = self::MODIFY_PERMISSIONS) {
    if (!$this->streamWrapperManager->isValidUri($directory)) {
      // Only trim if we're not dealing with a stream.
      $directory = rtrim($directory, '/\\');
    }

    if (!is_dir($directory)) {
      if (!($options & static::CREATE_DIRECTORY)) {
        return FALSE;
      }

      // Let mkdir() recursively create directories and use the default
      // directory permissions.
      $success = @$this->mkdir($directory, NULL, TRUE);
      if ($success) {
        return TRUE;
      }
      // If the operation failed, check again if the directory was created
      // by another process/server, only report a failure if not. In this case
      // we still need to ensure the directory is writable.
      if (!is_dir($directory)) {
        return FALSE;
      }
    }

    $writable = is_writable($directory);
    if (!$writable && ($options & static::MODIFY_PERMISSIONS)) {
      return $this->chmod($directory);
    }

    return $writable;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationFilename($destination, /* FileExists */$fileExists) {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    $basename = $this->basename($destination);
    if (!Unicode::validateUtf8($basename)) {
      throw new FileException(sprintf("Invalid filename '%s'", $basename));
    }
    if (file_exists($destination)) {
      switch ($fileExists) {
        case FileExists::Replace:
          // Do nothing here, we want to overwrite the existing file.
          break;

        case FileExists::Rename:
          $directory = $this->dirname($destination);
          $destination = $this->createFilename($basename, $directory);
          break;

        case FileExists::Error:
          // Error reporting handled by calling function.
          return FALSE;
      }
    }
    return $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function createFilename($basename, $directory) {
    $original = $basename;
    // Strip control characters (ASCII value < 32). Though these are allowed in
    // some filesystems, not many applications handle them well.
    $basename = preg_replace('/[\x00-\x1F]/u', '_', $basename);
    if (preg_last_error() !== PREG_NO_ERROR) {
      throw new FileException(sprintf("Invalid filename '%s'", $original));
    }
    if (str_starts_with(PHP_OS, 'WIN')) {
      // These characters are not allowed in Windows filenames.
      $basename = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $basename);
    }

    // A URI or path may already have a trailing slash or look like "public://".
    if (str_ends_with($directory, '/')) {
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
   * {@inheritdoc}
   */
  public function getTempDirectory() {
    // Use settings.
    $temporary_directory = $this->settings->get('file_temp_path');
    if (!empty($temporary_directory)) {
      return $temporary_directory;
    }

    // Fallback to OS default.
    $temporary_directory = FileSystemComponent::getOsTemporaryDirectory();

    if (empty($temporary_directory)) {
      // If no directory has been found default to 'files/tmp'.
      $temporary_directory = PublicStream::basePath() . '/tmp';

      // Windows accepts paths with either slash (/) or backslash (\), but
      // will not accept a path which contains both a slash and a backslash.
      // Since the 'file_public_path' variable may have either format, we
      // sanitize everything to use slash which is supported on all platforms.
      $temporary_directory = str_replace('\\', '/', $temporary_directory);
    }
    return $temporary_directory;
  }

  /**
   * {@inheritdoc}
   */
  public function scanDirectory($dir, $mask, array $options = []) {
    // Merge in defaults.
    $options += [
      'callback' => 0,
      'recurse' => TRUE,
      'key' => 'uri',
      'min_depth' => 0,
    ];
    $dir = $this->streamWrapperManager->normalizeUri($dir);
    if (!is_dir($dir)) {
      throw new NotRegularDirectoryException("$dir is not a directory.");
    }
    // Allow directories specified in settings.php to be ignored. You can use
    // this to not check for files in common special-purpose directories. For
    // example, node_modules and bower_components. Ignoring irrelevant
    // directories is a performance boost.
    if (!isset($options['nomask'])) {
      $ignore_directories = $this->settings->get('file_scan_ignore_directories', []);
      array_walk($ignore_directories, function (&$value) {
        $value = preg_quote($value, '/');
      });
      $options['nomask'] = '/^' . implode('|', $ignore_directories) . '$/';
    }
    $options['key'] = in_array($options['key'], ['uri', 'filename', 'name']) ? $options['key'] : 'uri';
    return $this->doScanDirectory($dir, $mask, $options);
  }

  /**
   * Internal function to handle directory scanning with recursion.
   *
   * @param string $dir
   *   The base directory or URI to scan, without trailing slash.
   * @param string $mask
   *   The preg_match() regular expression for files to be included.
   * @param array $options
   *   The options as per ::scanDirectory().
   * @param int $depth
   *   The current depth of recursion.
   *
   * @return array
   *   An associative array as per ::scanDirectory().
   *
   * @throws \Drupal\Core\File\Exception\NotRegularDirectoryException
   *   If the directory does not exist.
   *
   * @see \Drupal\Core\File\FileSystemInterface::scanDirectory()
   */
  protected function doScanDirectory($dir, $mask, array $options = [], $depth = 0) {
    $files_in_sub_dirs = [];
    $files_in_this_directory = [];
    // Avoid warnings when opendir does not have the permissions to open a
    // directory.
    if ($handle = @opendir($dir)) {
      while (FALSE !== ($filename = readdir($handle))) {
        // Skip this file if it matches the nomask or starts with a dot.
        if ($filename[0] != '.' && !(preg_match($options['nomask'], $filename))) {
          if (str_ends_with($dir, '/')) {
            $uri = "$dir$filename";
          }
          else {
            $uri = "$dir/$filename";
          }
          if ($options['recurse'] && is_dir($uri)) {
            $files_in_sub_dirs[] = $this->doScanDirectory($uri, $mask, $options, $depth + 1);
          }
          elseif ($depth >= $options['min_depth'] && preg_match($mask, $filename)) {
            // Always use this match over anything already set with the same
            // $options['key'].
            $file = new \stdClass();
            $file->uri = $uri;
            $file->filename = $filename;
            $file->name = pathinfo($filename, PATHINFO_FILENAME);
            $key = $options['key'];
            $files_in_this_directory[$file->$key] = $file;
            if ($options['callback']) {
              $options['callback']($uri);
            }
          }
        }
      }
      closedir($handle);
    }

    // Give priority to files in this folder by merging them after
    // any subdirectory files.
    return array_merge(array_merge(...$files_in_sub_dirs), $files_in_this_directory);
  }

}
