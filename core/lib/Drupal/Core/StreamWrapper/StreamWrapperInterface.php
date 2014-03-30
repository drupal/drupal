<?php

/**
 * @file
 * Definition of Drupal\Core\StreamWrapper\StreamWrapperInterface.
 *
 * Provides a Drupal interface and classes to implement PHP stream wrappers for
 * public, private, and temporary files.
 *
 * A stream wrapper is an abstraction of a file system that allows Drupal to
 * use the same set of methods to access both local files and remote resources.
 *
 * Note that PHP 5.2 fopen() only supports URIs of the form "scheme://target"
 * despite the fact that according to RFC 3986 a URI's scheme component
 * delimiter is in general just ":", not "://".  Because of this PHP limitation
 * and for consistency Drupal will only accept URIs of form "scheme://target".
 *
 * @see http://www.faqs.org/rfcs/rfc3986.html
 * @see http://bugs.php.net/bug.php?id=47070
 */

namespace Drupal\Core\StreamWrapper;

/**
 * Defines a Drupal stream wrapper extension.
 *
 * Extends the StreamWrapperInterface with methods expected by Drupal stream
 * wrapper classes.
 */
interface StreamWrapperInterface extends PhpStreamWrapperInterface {

  /**
   * Sets the absolute stream resource URI.
   *
   * This allows you to set the URI. Generally is only called by the factory
   * method.
   *
   * @param string $uri
   *   A string containing the URI that should be used for this instance.
   */
  public function setUri($uri);

  /**
   * Returns the stream resource URI.
   *
   * @return string
   *   Returns the current URI of the instance.
   */
  public function getUri();

  /**
   * Returns a web accessible URL for the resource.
   *
   * This function should return a URL that can be embedded in a web page
   * and accessed from a browser. For example, the external URL of
   * "youtube://xIpLd0WQKCY" might be
   * "http://www.youtube.com/watch?v=xIpLd0WQKCY".
   *
   * @return string
   *   Returns a string containing a web accessible URL for the resource.
   */
  public function getExternalUrl();

  /**
   * Returns the MIME type of the resource.
   *
   * @param string $uri
   *   The URI, path, or filename.
   * @param array $mapping
   *   An optional map of extensions to their mimetypes, in the form:
   *    - 'mimetypes': a list of mimetypes, keyed by an identifier,
   *    - 'extensions': the mapping itself, an associative array in which
   *      the key is the extension and the value is the mimetype identifier.
   *
   * @return string
   *   Returns a string containing the MIME type of the resource.
   */
  public static function getMimeType($uri, $mapping = NULL);

  /**
   * Returns canonical, absolute path of the resource.
   *
   * Implementation placeholder. PHP's realpath() does not support stream
   * wrappers. We provide this as a default so that individual wrappers may
   * implement their own solutions.
   *
   * @return string
   *   Returns a string with absolute pathname on success (implemented
   *   by core wrappers), or FALSE on failure or if the registered
   *   wrapper does not provide an implementation.
   */
  public function realpath();

  /**
   * Gets the name of the directory from a given path.
   *
   * This method is usually accessed through drupal_dirname(), which wraps
   * around the normal PHP dirname() function, which does not support stream
   * wrappers.
   *
   * @param string $uri
   *   An optional URI.
   *
   * @return string
   *   A string containing the directory name, or FALSE if not applicable.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL);

}
