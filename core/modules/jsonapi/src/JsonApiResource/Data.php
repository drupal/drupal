<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;

/**
 * Represents the `data` and `included` objects of a top-level object.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
abstract class Data implements \IteratorAggregate, \Countable {

  /**
   * Various representations of JSON:API objects.
   *
   * @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface[]
   */
  protected $data;

  /**
   * The number of resources permitted in this collection.
   *
   * @var int
   */
  protected $cardinality;

  /**
   * Holds a boolean indicating if there is a next page.
   *
   * @var bool
   */
  protected $hasNextPage;

  /**
   * Holds the total count of entities.
   *
   * @var int
   */
  protected $count;

  /**
   * Instantiates a Data object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface[] $data
   *   The resources or resource identifiers for the collection.
   * @param int $cardinality
   *   The number of resources that this collection may contain. Related
   *   resource collections may handle both to-one or to-many relationships. A
   *   to-one relationship should have a cardinality of 1. Use -1 for unlimited
   *   cardinality.
   */
  public function __construct(array $data, $cardinality = -1) {
    assert(Inspector::assertAllObjects($data, ResourceIdentifierInterface::class));
    assert($cardinality >= -1 && $cardinality !== 0, 'Cardinality must be -1 for unlimited cardinality or a positive integer.');
    assert($cardinality === -1 || count($data) <= $cardinality, 'If cardinality is not unlimited, the number of given resources must not exceed the cardinality of the collection.');
    $this->data = array_values($data);
    $this->cardinality = $cardinality;
  }

  /**
   * Returns an iterator for entities.
   *
   * @return \ArrayIterator
   *   An \ArrayIterator instance
   */
  public function getIterator() {
    return new \ArrayIterator($this->data);
  }

  /**
   * Returns the number of entities.
   *
   * @return int
   *   The number of parameters
   */
  public function count() {
    return count($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function setTotalCount($count) {
    $this->count = $count;
  }

  /**
   * Returns the collection as an array.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of entities.
   */
  public function toArray() {
    return $this->data;
  }

  /**
   * Checks if there is a next page in the collection.
   *
   * @return bool
   *   TRUE if the collection has a next page.
   */
  public function hasNextPage() {
    return (bool) $this->hasNextPage;
  }

  /**
   * Sets the has next page flag.
   *
   * Once the collection query has been executed and we build the entity
   * collection, we now if there will be a next page with extra entities.
   *
   * @param bool $has_next_page
   *   TRUE if the collection has a next page.
   */
  public function setHasNextPage($has_next_page) {
    $this->hasNextPage = (bool) $has_next_page;
  }

  /**
   * Gets the cardinality of this collection.
   *
   * @return int
   *   The cardinality of the resource collection. -1 for unlimited cardinality.
   */
  public function getCardinality() {
    return $this->cardinality;
  }

  /**
   * Returns a new Data object containing the entities of $this and $other.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Data $a
   *   A Data object object to be merged.
   * @param \Drupal\jsonapi\JsonApiResource\Data $b
   *   A Data object to be merged.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Data
   *   A new merged Data object.
   */
  public static function merge(Data $a, Data $b) {
    return new static(array_merge($a->toArray(), $b->toArray()));
  }

  /**
   * Returns a new, deduplicated Data object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Data $collection
   *   The Data object to deduplicate.
   *
   * @return static
   *   A new merged Data object.
   */
  public static function deduplicate(Data $collection) {
    $deduplicated = [];
    foreach ($collection as $resource) {
      $dedupe_key = $resource->getTypeName() . ':' . $resource->getId();
      if ($resource instanceof EntityAccessDeniedHttpException && ($error = $resource->getError()) && !is_null($error['relationship_field'])) {
        $dedupe_key .= ':' . $error['relationship_field'];
      }
      $deduplicated[$dedupe_key] = $resource;
    }
    return new static(array_values($deduplicated));
  }

}
