<?php

/**
 * @file
 * Contains \Drupal\Core\StreamWrapper\StreamWrapperManager.
 */

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Provides a StreamWrapper manager.
 *
 * @see file_get_stream_wrappers()
 * @see hook_stream_wrappers_alter()
 * @see system_stream_wrappers()
 * @see \Drupal\Core\StreamWrapper\StreamWrapperInterface
 */
class StreamWrapperManager extends ContainerAware {

  /**
   * Contains stream wrapper info.
   *
   * An associative array where keys are scheme names and values are themselves
   * associative arrays with the keys class, type and (optionally) service_id,
   * and string values.
   *
   * @var array
   */
  protected $info = array();

  /**
   * Contains collected stream wrappers.
   *
   * Keyed by filter, each value is itself an associative array keyed by scheme.
   * Each of those values is an array representing a stream wrapper, with the
   * following keys and values:
   *   - class: stream wrapper class name
   *   - type: a bitmask corresponding to the type constants in
   *     StreamWrapperInterface
   *   - service_id: name of service
   *
   * The array on key StreamWrapperInterface::ALL contains representations of
   * all schemes and corresponding wrappers.
   *
   * @var array
   */
  protected $wrappers = array();

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a StreamWrapperManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Provides Drupal stream wrapper registry.
   *
   * A stream wrapper is an abstraction of a file system that allows Drupal to
   * use the same set of methods to access both local files and remote
   * resources.
   *
   * Provide a facility for managing and querying user-defined stream wrappers
   * in PHP. PHP's internal stream_get_wrappers() doesn't return the class
   * registered to handle a stream, which we need to be able to find the handler
   * for class instantiation.
   *
   * If a module registers a scheme that is already registered with PHP, the
   * existing scheme will be unregistered and replaced with the specified class.
   *
   * A stream is referenced as "scheme://target".
   *
   * The optional $filter parameter can be used to retrieve only the stream
   * wrappers that are appropriate for particular usage. For example, this
   * returns only stream wrappers that use local file storage:
   *
   * @code
   *   $local_stream_wrappers = file_get_stream_wrappers(StreamWrapperInterface::LOCAL);
   * @endcode
   *
   * The $filter parameter can only filter to types containing a particular
   * flag. In some cases, you may want to filter to types that do not contain a
   * particular flag. For example, you may want to retrieve all stream wrappers
   * that are not writable, or all stream wrappers that are not local. PHP's
   * array_diff_key() function can be used to help with this. For example, this
   * returns only stream wrappers that do not use local file storage:
   * @code
   *   $remote_stream_wrappers = array_diff_key(file_get_stream_wrappers(StreamWrapperInterface::ALL), file_get_stream_wrappers(StreamWrapperInterface::LOCAL));
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
  public function getWrappers($filter = StreamWrapperInterface::ALL) {
    if (isset($this->wrappers[$filter])) {
      return $this->wrappers[$filter];
    }
    else if (isset($this->wrappers[StreamWrapperInterface::ALL])) {
      $this->wrappers[$filter] = array();
      foreach ($this->wrappers[StreamWrapperInterface::ALL] as $scheme => $info) {
        // Bit-wise filter.
        if (($info['type'] & $filter) == $filter) {
          $this->wrappers[$filter][$scheme] = $info;
        }
      }
      return $this->wrappers[$filter];
    }
    else {
      return array();
    }
  }

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
  public function getNames($filter = StreamWrapperInterface::ALL) {
    $names = array();
    foreach (array_keys($this->getWrappers($filter)) as $scheme) {
      $names[$scheme] = $this->getViaScheme($scheme)->getName();
    }

    return $names;
  }

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
  public function getDescriptions($filter = StreamWrapperInterface::ALL) {
    $descriptions = array();
    foreach (array_keys($this->getWrappers($filter)) as $scheme) {
      $descriptions[$scheme] = $this->getViaScheme($scheme)->getDescription();
    }

    return $descriptions;
  }

  /**
   * Returns a stream wrapper via scheme.
   *
   * @param string $scheme
   *   The scheme of the stream wrapper.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|bool
   *   A stream wrapper object, or false if the scheme is not available.
   */
  public function getViaScheme($scheme) {
    return $this->getWrapper($scheme, $scheme . '://');
  }

  /**
   * Returns a stream wrapper via URI.
   *
   * @param string $uri
   *   The URI of the stream wrapper.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|bool
   *   A stream wrapper object, or false if the scheme is not available.
   */
  public function getViaUri($uri) {
    $scheme = file_uri_scheme($uri);
    return $this->getWrapper($scheme, $uri);
  }

  /**
   * Returns the stream wrapper class.
   *
   * @param string $scheme
   *   The stream wrapper scheme.
   *
   * @return string|bool
   *   The stream wrapper class, or false if the scheme does not exist.
   */
  public function getClass($scheme) {
    if (isset($this->info[$scheme])) {
      return $this->info[$scheme]['class'];
    }

    return FALSE;
  }

  /**
   * Returns a stream wrapper instance.
   *
   * @param string $scheme
   *   The scheme of the desired stream wrapper.
   * @param string $uri
   *   The URI of the stream.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|bool
   *   A stream wrapper object, or false if the scheme is not available.
   */
  protected function getWrapper($scheme, $uri) {
    if (isset($this->info[$scheme]['service_id'])) {
      $instance = $this->container->get($this->info[$scheme]['service_id']);
      $instance->setUri($uri);
      return $instance;
    }

    return FALSE;
  }

  /**
   * Adds a stream wrapper.
   *
   * Internal use only.
   *
   * @param string $service_id
   *   The service id.
   * @param string $class
   *   The stream wrapper class.
   * @param string $scheme
   *   The scheme for which the wrapper should be registered.
   */
  public function addStreamWrapper($service_id, $class, $scheme) {
    $this->info[$scheme] = array(
      'class' => $class,
      'type' => $class::getType(),
      'service_id' => $service_id,
    );
  }

  /**
   * Registers the tagged stream wrappers.
   *
   * Internal use only.
   */
  public function register() {
    $this->moduleHandler->alter('stream_wrappers', $this->info);

    foreach ($this->info as $scheme => $info) {
      $this->registerWrapper($scheme, $info['class'], $info['type']);
    }
  }

  /**
   * Unregisters the tagged stream wrappers.
   *
   * Internal use only.
   */
  public function unregister() {
    // Normally, there are definitely wrappers set for the ALL filter. However,
    // in some cases involving many container rebuilds (e.g. WebTestBase),
    // $this->wrappers may be empty although wrappers are still registered
    // globally. Thus an isset() check is needed before iterating.
    if (isset($this->wrappers[StreamWrapperInterface::ALL])) {
      foreach (array_keys($this->wrappers[StreamWrapperInterface::ALL]) as $scheme) {
        stream_wrapper_unregister($scheme);
      }
    }
  }

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
  public function registerWrapper($scheme, $class, $type) {
    if (in_array($scheme, stream_get_wrappers(), TRUE)) {
      stream_wrapper_unregister($scheme);
    }

    if (($type & StreamWrapperInterface::LOCAL) == StreamWrapperInterface::LOCAL) {
      stream_wrapper_register($scheme, $class);
    }
    else {
      stream_wrapper_register($scheme, $class, STREAM_IS_URL);
    }

    // Pre-populate the static cache with the filters most typically used.
    $info = array('type' => $type, 'class' => $class);
    $this->wrappers[StreamWrapperInterface::ALL][$scheme] = $info;

    if (($type & StreamWrapperInterface::WRITE_VISIBLE) == StreamWrapperInterface::WRITE_VISIBLE) {
      $this->wrappers[StreamWrapperInterface::WRITE_VISIBLE][$scheme] = $info;
    }
  }

}
