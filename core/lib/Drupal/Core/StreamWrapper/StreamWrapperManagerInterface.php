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

}
