<?php

declare(strict_types = 1);

namespace Drupal\jsonapi\Events;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event for collecting resource object metadata of a JSON:API resource types.
 *
 * Subscribers of this event that call ::setMetaValue() must set the appropriate
 * cache tags and contexts. These should be based on the data that is used to
 * set the meta. These will then bubble up to the normalization.
 *
 * @Event
 */
final class CollectResourceObjectMetaEvent extends Event implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The metadata.
   *
   * @var array
   */
  private array $meta = [];

  /**
   * Constructs a new CollectResourceObjectMetaEvent object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $resourceObject
   *   The resource object.
   * @param array $context
   *   The context options for the normalizer.
   */
  public function __construct(
    private readonly ResourceObject $resourceObject,
    private readonly array $context,
  ) {
    if (empty($context['resource_object']) || !($context['resource_object'] instanceof ResourceObject) || $this->context['resource_object']->getId() !== $this->resourceObject->getId()) {
      throw new \RuntimeException('The context must contain a valid resource object.');
    }
  }

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
   * Gets context options for the normalizer.
   *
   * @return array
   *   The context options for the normalizer.
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * Gets the meta values.
   *
   * @return array
   *   The meta data.
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
