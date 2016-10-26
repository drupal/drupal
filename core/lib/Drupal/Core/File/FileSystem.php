<?php

namespace Drupal\Core\File;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides helpers to operate on files and stream wrappers.
 */
class FileSystem implements FileSystemInterface {

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
   * The file logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The file logger channel.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager, Settings $settings, LoggerInterface $logger) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->settings = $settings;
    $this->logger = $logger;
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

    if (@chmod($uri, $mode)) {
      return TRUE;
    }

    $this->logger->error('The file permissions could not be set on %uri.', array('%uri' => $uri));
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($uri, $context = NULL) {
    $scheme = $this->uriScheme($uri);
    if (!$this->validScheme($scheme) && (substr(PHP_OS, 0, 3) == 'WIN')) {
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
    $scheme = $this->uriScheme($uri);

    if ($this->validScheme($scheme)) {
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
    if ($this->uriScheme($uri)) {
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
          if (!$this->mkdirCall($recursive_path, $mode, FALSE, $context)) {
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
   * Helper function. Ensures we don't pass a NULL as a context resource to
   * mkdir().
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
    $scheme = $this->uriScheme($uri);
    if (!$this->validScheme($scheme) && (substr(PHP_OS, 0, 3) == 'WIN')) {
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
    $scheme = $this->uriScheme($directory);

    if ($this->validScheme($scheme)) {
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
  public function uriScheme($uri) {
    if (preg_match('/^([\w\-]+):\/\/|^(data):/', $uri, $matches)) {
      // The scheme will always be the last element in the matches array.
      return array_pop($matches);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validScheme($scheme) {
    if (!$scheme) {
      return FALSE;
    }
    return class_exists($this->streamWrapperManager->getClass($scheme));
  }

}
