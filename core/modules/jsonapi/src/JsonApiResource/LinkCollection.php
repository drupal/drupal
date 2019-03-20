<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;

/**
 * Contains a set of JSON:API Link objects.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
final class LinkCollection implements \IteratorAggregate {

  /**
   * The links in the collection, keyed by unique strings.
   *
   * @var \Drupal\jsonapi\JsonApiResource\Link[]
   */
  protected $links;

  /**
   * The link context.
   *
   * All links objects exist within a context object. Links form a relationship
   * between a source IRI and target IRI. A context is the link's source.
   *
   * @var \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject
   *
   * @see https://tools.ietf.org/html/rfc8288#section-3.2
   */
  protected $context;

  /**
   * LinkCollection constructor.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link[] $links
   *   An associated array of key names and JSON:API Link objects.
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   (internal use only) The context object. Use the self::withContext()
   *   method to establish a context. This should be done automatically when
   *   a LinkCollection is passed into a context object.
   */
  public function __construct(array $links, $context = NULL) {
    assert(Inspector::assertAll(function ($key) {
      return static::validKey($key);
    }, array_keys($links)));
    assert(Inspector::assertAll(function ($link) {
      return $link instanceof Link || is_array($link) && Inspector::assertAllObjects($link, Link::class);
    }, $links));
    assert(is_null($context) || Inspector::assertAllObjects([$context], JsonApiDocumentTopLevel::class, ResourceObject::class));
    ksort($links);
    $this->links = array_map(function ($link) {
      return is_array($link) ? $link : [$link];
    }, $links);
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    assert(!is_null($this->context), 'A LinkCollection is invalid unless a context has been established.');
    return new \ArrayIterator($this->links);
  }

  /**
   * Gets a new LinkCollection with the given link inserted.
   *
   * @param string $key
   *   A key for the link. If the key already exists and the link shares an href
   *   with an existing link with that key, those links will be merged together.
   * @param \Drupal\jsonapi\JsonApiResource\Link $new_link
   *   The link to insert.
   *
   * @return static
   *   A new LinkCollection with the given link inserted or merged with the
   *   current set of links.
   */
  public function withLink($key, Link $new_link) {
    assert(static::validKey($key));
    $merged = $this->links;
    if (isset($merged[$key])) {
      foreach ($merged[$key] as $index => $existing_link) {
        if (Link::compare($existing_link, $new_link) === 0) {
          $merged[$key][$index] = Link::merge($existing_link, $new_link);
          return new static($merged, $this->context);
        }
      }
    }
    $merged[$key][] = $new_link;
    return new static($merged, $this->context);
  }

  /**
   * Whether a link with the given key exists.
   *
   * @param string $key
   *   The key.
   *
   * @return bool
   *   TRUE if a link with the given key exist, FALSE otherwise.
   */
  public function hasLinkWithKey($key) {
    return array_key_exists($key, $this->links);
  }

  /**
   * Establishes a new context for a LinkCollection.
   *
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The new context object.
   *
   * @return static
   *   A new LinkCollection with the given context.
   */
  public function withContext($context) {
    return new static($this->links, $context);
  }

  /**
   * Gets the LinkCollection's context object.
   *
   * @return \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject
   *   The LinkCollection's context.
   */
  public function getContext() {
    assert(!is_null($this->context), 'A LinkCollection is invalid unless a context has been established.');
    return $this->context;
  }

  /**
   * Filters a LinkCollection using the provided callback.
   *
   * @param callable $f
   *   The filter callback. The callback has the signature below.
   *
   * @code
   *   boolean callback(string $key, \Drupal\jsonapi\JsonApiResource\Link $link, mixed $context))
   * @endcode
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   A new, filtered LinkCollection.
   */
  public function filter(callable $f) {
    $links = iterator_to_array($this);
    $filtered = array_reduce(array_keys($links), function ($filtered, $key) use ($links, $f) {
      if ($f($key, $links[$key], $this->context)) {
        $filtered[$key] = $links[$key];
      }
      return $filtered;
    }, []);
    return new LinkCollection($filtered, $this->context);
  }

  /**
   * Merges two LinkCollections.
   *
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $a
   *   The first link collection.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $b
   *   The second link collection.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   A new LinkCollection with the links of both inputs.
   */
  public static function merge(LinkCollection $a, LinkCollection $b) {
    assert($a->getContext() === $b->getContext());
    $merged = new LinkCollection([], $a->getContext());
    foreach ($a as $key => $links) {
      $merged = array_reduce($links, function (self $merged, Link $link) use ($key) {
        return $merged->withLink($key, $link);
      }, $merged);
    }
    foreach ($b as $key => $links) {
      $merged = array_reduce($links, function (self $merged, Link $link) use ($key) {
        return $merged->withLink($key, $link);
      }, $merged);
    }
    return $merged;
  }

  /**
   * Ensures that a link key is valid.
   *
   * @param string $key
   *   A key name.
   *
   * @return bool
   *   TRUE if the key is valid, FALSE otherwise.
   */
  protected static function validKey($key) {
    return is_string($key) && !is_numeric($key) && strpos($key, ':') === FALSE;
  }

}
