<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Provides a StreamWrapper manager.
 *
 * @see \Drupal\Core\StreamWrapper\StreamWrapperInterface
 */
class StreamWrapperManager implements StreamWrapperManagerInterface {

  /**
   * Constructs a StreamWrapperManager object.
   *
   * @param \Symfony\Component\DependencyInjection\ServiceLocator $container
   *   A service locator containing stream wrapper services, keyed by scheme.
   */
  public function __construct(
    #[AutowireLocator('stream_wrapper', indexAttribute: 'scheme')]
    protected readonly ServiceLocator $container,
  ) {}

  /**
   * Contains collected stream wrappers.
   *
   * Keyed by filter, each value is itself an associative array keyed by scheme.
   * Each of those values is an array representing a stream wrapper, with the
   * following keys and values:
   *   - class: stream wrapper class name
   *   - type: a bitmask corresponding to the type constants in
   *     StreamWrapperInterface
   *
   * The array on key StreamWrapperInterface::ALL contains representations of
   * all schemes and corresponding wrappers.
   *
   * @var array
   */
  protected $wrappers = [];

  /**
   * {@inheritdoc}
   */
  public function getWrappers($filter = StreamWrapperInterface::ALL) {
    if (isset($this->wrappers[$filter])) {
      return $this->wrappers[$filter];
    }
    elseif (isset($this->wrappers[StreamWrapperInterface::ALL])) {
      $this->wrappers[$filter] = [];
      foreach ($this->wrappers[StreamWrapperInterface::ALL] as $scheme => $info) {
        // Bit-wise filter.
        if (($info['type'] & $filter) == $filter) {
          $this->wrappers[$filter][$scheme] = $info;
        }
      }
      return $this->wrappers[$filter];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNames($filter = StreamWrapperInterface::ALL) {
    $names = [];
    foreach (array_keys($this->getWrappers($filter)) as $scheme) {
      $names[$scheme] = $this->getViaScheme($scheme)->getName();
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescriptions($filter = StreamWrapperInterface::ALL) {
    $descriptions = [];
    foreach (array_keys($this->getWrappers($filter)) as $scheme) {
      $descriptions[$scheme] = $this->getViaScheme($scheme)->getDescription();
    }

    return $descriptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getViaScheme($scheme) {
    return $this->getWrapper($scheme, $scheme . '://');
  }

  /**
   * {@inheritdoc}
   */
  public function getViaUri($uri) {
    $scheme = static::getScheme($uri);
    return $this->getWrapper($scheme, $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getClass($scheme) {
    if ($this->container->has($scheme)) {
      return get_class($this->container->get($scheme));
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
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|false
   *   A stream wrapper object, or false if the scheme is not available.
   */
  protected function getWrapper($scheme, $uri) {
    if ($this->container->has($scheme)) {
      $instance = $this->container->get($scheme);
      $instance->setUri($uri);
      return $instance;
    }

    return FALSE;
  }

  /**
   * Registers the tagged stream wrappers.
   *
   * Internal use only.
   */
  public function register() {
    foreach (array_keys($this->container->getProvidedServices()) as $scheme) {
      $class = $this->getClass($scheme);
      $this->registerWrapper($scheme, $class, $class::getType());
    }
  }

  /**
   * Deregisters the tagged stream wrappers.
   *
   * Internal use only.
   */
  public function unregister() {
    // Normally, there are definitely wrappers set for the ALL filter. However,
    // in some cases involving many container rebuilds (e.g. BrowserTestBase),
    // $this->wrappers may be empty although wrappers are still registered
    // globally. Thus an isset() check is needed before iterating.
    if (isset($this->wrappers[StreamWrapperInterface::ALL])) {
      foreach (array_keys($this->wrappers[StreamWrapperInterface::ALL]) as $scheme) {
        stream_wrapper_unregister($scheme);
      }
    }
  }

  /**
   * {@inheritdoc}
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
    $info = ['type' => $type, 'class' => $class];
    $this->wrappers[StreamWrapperInterface::ALL][$scheme] = $info;

    if (($type & StreamWrapperInterface::WRITE_VISIBLE) == StreamWrapperInterface::WRITE_VISIBLE) {
      $this->wrappers[StreamWrapperInterface::WRITE_VISIBLE][$scheme] = $info;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getTarget($uri) {
    // Remove the scheme from the URI and remove erroneous leading or trailing,
    // forward-slashes and backslashes.
    $target = trim(preg_replace('/^[\w\-]+:\/\/|^data:/', '', $uri), '\/');

    // If nothing was replaced, the URI doesn't have a valid scheme.
    return $target !== $uri ? $target : FALSE;
  }

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
  public function normalizeUri($uri) {
    $scheme = $this->getScheme($uri);

    if ($this->isValidScheme($scheme)) {
      $target = $this->getTarget($uri);

      if ($target !== FALSE) {

        if (!in_array($scheme, Settings::get('file_sa_core_2023_005_schemes', []))) {
          $class = $this->getClass($scheme);
          $is_local = is_subclass_of($class, LocalStream::class);
          if ($is_local) {
            $target = str_replace(DIRECTORY_SEPARATOR, '/', $target);
          }

          $parts = explode('/', $target);
          $normalized_parts = [];
          while ($parts) {
            $part = array_shift($parts);
            if ($part === '' || $part === '.') {
              continue;
            }
            elseif ($part === '..' && $is_local && $normalized_parts === []) {
              $normalized_parts[] = $part;
              break;
            }
            elseif ($part === '..') {
              array_pop($normalized_parts);
            }
            else {
              $normalized_parts[] = $part;
            }
          }

          $target = implode('/', array_merge($normalized_parts, $parts));
        }

        $uri = $scheme . '://' . $target;
      }
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public static function getScheme($uri) {
    if (preg_match('/^([\w\-]+):\/\/|^(data):/', $uri, $matches)) {
      // The scheme will always be the last element in the matches array.
      return array_pop($matches);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidScheme($scheme) {
    if (!$scheme) {
      return FALSE;
    }
    return class_exists($this->getClass($scheme));
  }

  /**
   * {@inheritdoc}
   */
  public function isValidUri($uri) {
    // Assert that the URI has an allowed scheme. Bare paths are not allowed.
    return $this->isValidScheme($this->getScheme($uri));
  }

}
