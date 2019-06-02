<?php

namespace Drupal\Core\StreamWrapper;

/**
 * Provides a StreamWrapper manager.
 *
 * @see \Drupal\Core\StreamWrapper\StreamWrapperInterface
 */
interface StreamWrapperManagerInterface {

  /**
   * Provides Drupal stream wrapper registry.
   *
   * A stream wrapper is an abstraction of a file system that allows Drupal to
   * use the same set of methods to access both local files and remote
   * resources.
   *
   * Provide a facility for managing and querying user-defined stream wrappers
   * in PHP. PHP's internal stream_get_wrappers() doesn't return the class
   * registered to handle a stream, which we need to be able to find the
   * handler
   * for class instantiation.
   *
   * If a module registers a scheme that is already registered with PHP, the
   * existing scheme will be unregistered and replaced with the specified
   * class.
   *
   * A stream is referenced as "scheme://target".
   *
   * The optional $filter parameter can be used to retrieve only the stream
   * wrappers that are appropriate for particular usage. For example, this
   * returns only stream wrappers that use local file storage:
   *
   * @code
   *   $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
   *   $local_stream_wrappers = $stream_wrapper_manager->getWrappers(StreamWrapperInterface::LOCAL);
   * @endcode
   *
   * The $filter parameter can only filter to types containing a particular
   * flag. In some cases, you may want to filter to types that do not contain a
   * particular flag. For example, you may want to retrieve all stream wrappers
   * that are not writable, or all stream wrappers that are not local. PHP's
   * array_diff_key() function can be used to help with this. For example, this
   * returns only stream wrappers that do not use local file storage:
   * @code
   *   $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
   *   $remote_stream_wrappers = array_diff_key(
   *     $stream_wrapper_manager->getWrappers(StreamWrapperInterface::ALL),
   *     $stream_wrapper_manager->getWrappers(StreamWrapperInterface::LOCAL)
   *   );
   * @endcode
   *
   * @param int $filter
   *   (Optional) Filters out all types except those with an on bit for each on
   *   bit in $filter. For example, if $filter is
   *   StreamWrapperInterface::WRITE_VISIBLE, which is equal to
   *   (StreamWrapperInterface::READ | StreamWrapperInterface::WRITE |
   *   StreamWrapperInterface::VISIBLE), then only stream wrappers with all
   *   three of these bits set are returned. Defaults to
   *   StreamWrapperInterface::ALL, which returns all registered stream
   *   wrappers.
   *
   * @return array
   *   An array keyed by scheme, with values containing an array of information
   *   about the stream wrapper, as returned by hook_stream_wrappers(). If
   *   $filter is omitted or set to StreamWrapperInterface::ALL, the entire
   *   Drupal stream wrapper registry is returned. Otherwise only the stream
   *   wrappers whose 'type' bitmask has an on bit for each bit specified in
   *   $filter are returned.
   */
  public function getWrappers($filter = StreamWrapperInterface::ALL);

  /**
   * Returns registered stream wrapper names.
   *
   * @param int $filter
   *   (Optional) Filters out all types except those with an on bit for each on
   *   bit in $filter. For example, if $filter is
   *   StreamWrapperInterface::WRITE_VISIBLE, which is equal to
   *   (StreamWrapperInterface::READ | StreamWrapperInterface::WRITE |
   *   StreamWrapperInterface::VISIBLE), then only stream wrappers with all
   *   three of these bits set are returned. Defaults to
   *   StreamWrapperInterface::ALL, which returns all registered stream
   *   wrappers.
   *
   * @return array
   *   Stream wrapper names, keyed by scheme.
   */
  public function getNames($filter = StreamWrapperInterface::ALL);

  /**
   * Returns registered stream wrapper descriptions.
   *
   * @param int $filter
   *   (Optional) Filters out all types except those with an on bit for each on
   *   bit in $filter. For example, if $filter is
   *   StreamWrapperInterface::WRITE_VISIBLE, which is equal to
   *   (StreamWrapperInterface::READ | StreamWrapperInterface::WRITE |
   *   StreamWrapperInterface::VISIBLE), then only stream wrappers with all
   *   three of these bits set are returned. Defaults to
   *   StreamWrapperInterface::ALL, which returns all registered stream
   *   wrappers.
   *
   * @return array
   *   Stream wrapper descriptions, keyed by scheme.
   */
  public function getDescriptions($filter = StreamWrapperInterface::ALL);

  /**
   * Returns a reference to the stream wrapper class responsible for a scheme.
   *
   * This helper method returns a stream instance using a scheme. That is, the
   * passed string does not contain a "://". For example, "public" is a scheme
   * but "public://" is a URI (stream). This is because the later contains both
   * a scheme and target despite target being empty.
   *
   * Note: the instance URI will be initialized to "scheme://" so that you can
   * make the customary method calls as if you had retrieved an instance by URI.
   *
   * @param string $scheme
   *   If the stream was "public://target", "public" would be the scheme.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|bool
   *   Returns a new stream wrapper object appropriate for the given $scheme.
   *   For example, for the public scheme a stream wrapper object
   *   (Drupal\Core\StreamWrapper\PublicStream).
   *   FALSE is returned if no registered handler could be found.
   */
  public function getViaScheme($scheme);

  /**
   * Returns a reference to the stream wrapper class responsible for a URI.
   *
   * The scheme determines the stream wrapper class that should be
   * used by consulting the stream wrapper registry.
   *
   * @param string $uri
   *   A stream, referenced as "scheme://target".
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|bool
   *   Returns a new stream wrapper object appropriate for the given URI or
   *   FALSE if no registered handler could be found. For example, a URI of
   *   "private://example.txt" would return a new private stream wrapper object
   *   (Drupal\Core\StreamWrapper\PrivateStream).
   */
  public function getViaUri($uri);

  /**
   * Returns the stream wrapper class name for a given scheme.
   *
   * @param string $scheme
   *   Stream scheme.
   *
   * @return string|bool
   *   Return string if a scheme has a registered handler, or FALSE.
   */
  public function getClass($scheme);

  /**
   * Registers stream wrapper with PHP.
   *
   * @param string $scheme
   *   The scheme of the stream wrapper.
   * @param string $class
   *   The class of the stream wrapper.
   * @param int $type
   *   The type of the stream wrapper.
   */
  public function registerWrapper($scheme, $class, $type);

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
   * @see \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getScheme()
   */
  public static function getTarget($uri);

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
  public function normalizeUri($uri);

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
   * @see \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getTarget()
   */
  public static function getScheme($uri);

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
  public function isValidScheme($scheme);

  /**
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * There must be a scheme and it must be a Drupal-provided scheme like
   * 'public', 'private', 'temporary', or an extension provided with
   * hook_stream_wrappers().
   *
   * @param string $uri
   *   The URI to be tested.
   *
   * @return bool
   *   TRUE if the URI is valid.
   */
  public function isValidUri($uri);

}
