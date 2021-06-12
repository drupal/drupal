<?php

namespace Drupal\Core\StreamWrapper;

/**
 * Provides common methods for local streams.
 */
trait LocalStreamTrait {

  /**
   * Gets the name of the directory from a given path.
   *
   * This method is usually accessed through drupal_dirname(), which wraps
   * around the PHP dirname() function because it does not support stream
   * wrappers.
   *
   * @param string $uri
   *   A URI or path.
   *
   * @return string
   *   A string containing the directory name.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme) = explode('://', $uri, 2);
    $dirname = dirname($this->getTarget($uri));

    return $dirname !== '.' ? "$scheme://$dirname" : "$scheme://";
  }

  /**
   * Returns the local writable target of the resource within the stream.
   *
   * This function should be used in place of calls to realpath() or similar
   * functions when attempting to determine the location of a file. While
   * functions like realpath() may return the location of a read-only file, this
   * method may return a URI or path suitable for writing that is completely
   * separate from the URI used for reading.
   *
   * @param string $uri
   *   Optional URI.
   *
   * @return string
   *   Returns a string representing a location suitable for writing of a file.
   *
   * @throws \InvalidArgumentException
   *   If a malformed $uri parameter is passed in.
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    $uri_parts = explode('://', $uri, 2);
    if (count($uri_parts) === 1) {
      // The delimiter ('://') was not found in $uri, malformed $uri passed.
      throw new \InvalidArgumentException("Malformed uri parameter passed: $uri");
    }

    // Remove erroneous leading or trailing forward-slashes and backslashes.
    return trim($uri_parts[1], '\/');
  }

}
