<?php

declare(strict_types = 1);

namespace Drupal\jsonapi\Events;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event for collecting the relationship metadata of a JSON:API resource object.
 *
 * Subscribers of this event that call ::setMetaValue() must set the appropriate
 * cache tags and contexts. These should be based on the data that is used to
 * set the meta. These will then bubble up to the normalization.
 *
 * @Event
 */
final class CollectRelationshipMetaEvent extends Event implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The metadata.
   *
   * @var array
   */
  private array $meta = [];

  /**
   * Constructs a new CollectRelationshipMetaEvent object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $resourceObject
   *   The resource object.
   * @param string $relationshipFieldName
   *   The relationship field name.
   */
  public function __construct(
    private readonly ResourceObject $resourceObject,
    private readonly string $relationshipFieldName,
  ) {}

  /**
   * Gets the resource object.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObject
   *   The resource object.
   */
  public function getResourceObject(): ResourceObject {
    return $this->resourceObject;
  }

  /**
   * Gets the relationship field.
   *
   * @return string
   *   The relationship field name.
   */
  public function getRelationshipFieldName(): string {
    return $this->relationshipFieldName;
  }

  /**
   * Gets the meta values.
   *
   * @return array
   *   The meta.
   */
  public function getMeta(): array {
    return $this->meta;
  }

  /**
   * Sets a meta value.
   *
   * @param array|string $property
   *   The key or array of keys.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setMetaValue(array|string $property, mixed $value): self {
    NestedArray::setValue($this->meta, (array) $property, $value, TRUE);
    return $this;
  }

}
