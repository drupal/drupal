<?php

namespace Drupal\Core\StreamWrapper;

/**
 * Defines a Drupal stream wrapper extension.
 *
 * Provides a Drupal interface and classes to implement PHP stream wrappers for
 * public, private, and temporary files. Extends the PhpStreamWrapperInterface
 * with methods expected by Drupal stream wrapper classes.
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
interface StreamWrapperInterface extends PhpStreamWrapperInterface {

  /**
   * Stream wrapper bit flags that are the basis for composite types.
   *
   * Note that 0x0002 is skipped, because it was the value of a constant that
   * has since been removed.
   */

  /**
   * A filter that matches all wrappers.
   */
  const ALL = 0x0000;

  /**
   * Refers to a local file system location.
   */
  const LOCAL = 0x0001;

  /**
   * Wrapper is readable (almost always true).
   */
  const READ = 0x0004;

  /**
   * Wrapper is writeable.
   */
  const WRITE = 0x0008;

  /**
   * Exposed in the UI and potentially web accessible.
   */
  const VISIBLE = 0x0010;

  /**
   * Composite stream wrapper bit flags that are usually used as the types.
   */

  /**
   * Defines the stream wrapper bit flag for a hidden file.
   *
   * This is not visible in the UI or accessible via web, but readable and
   * writable; for instance, the temporary directory for file uploads.
   */
  const HIDDEN = 0x000C;

  /**
   * Hidden, readable and writeable using local files.
   */
  const LOCAL_HIDDEN = 0x000D;

  /**
   * Visible, readable and writeable.
   */
  const WRITE_VISIBLE = 0x001C;

  /**
   * Visible and read-only.
   */
  const READ_VISIBLE = 0x0014;

  /**
   * This is the default 'type' flag. This does not include
   * StreamWrapperInterface::LOCAL, because PHP grants a greater trust level to
   * local files (for example, they can be used in an "include" statement,
   * regardless of the "allow_url_include" setting), so stream wrappers need to
   * explicitly opt-in to this.
   */
  const NORMAL = 0x001C;

  /**
   * Visible, readable and writeable using local files.
   */
  const LOCAL_NORMAL = 0x001D;

  /**
   * Returns the type of stream wrapper.
   *
   * @return int
   */
  public static function getType();

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName();

  /**
   * Returns the description of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper description.
   */
  public function getDescription();

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
