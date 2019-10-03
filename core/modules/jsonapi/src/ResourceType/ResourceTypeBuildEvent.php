<?php

namespace Drupal\jsonapi\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event used to configure the construction of a JSON:API resource type.
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 */
class ResourceTypeBuildEvent extends Event {

  /**
   * The JSON:API resource type name of the instance to be built.
   *
   * @var string
   */
  protected $resourceTypeName;

  /**
   * Whether the JSON:API resource type to be built should be disabled.
   *
   * @var bool
   */
  protected $disabled = FALSE;

  /**
   * ResourceTypeBuildEvent constructor.
   *
   * This constructor is protected by design. Use
   * static::createFromEntityTypeAndBundle() instead.
   *
   * @param string $resource_type_name
   *   A JSON:API resource type name.
   */
  protected function __construct($resource_type_name) {
    $this->resourceTypeName = $resource_type_name;
  }

  /**
   * Creates a new ResourceTypeBuildEvent.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type for the resource type to be built.
   * @param string $bundle
   *   A bundle name for the resource type to be built. If the entity type does
   *   not have bundles, the entity type ID.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent
   *   A new event.
   */
  public static function createFromEntityTypeAndBundle(EntityTypeInterface $entity_type, $bundle) {
    return new static(sprintf('%s--%s', $entity_type->id(), $bundle));
  }

  /**
   * Gets current resource type name of the resource type to be built.
   *
   * @return string
   *   The resource type name.
   */
  public function getResourceTypeName() {
    return $this->resourceTypeName;
  }

  /**
   * Disables the resource type to be built.
   */
  public function disableResourceType() {
    $this->disabled = TRUE;
  }

  /**
   * Whether the resource type to be built should be disabled.
   *
   * @return bool
   *   TRUE if the resource type should be disabled, FALSE otherwise.
   */
  public function resourceTypeShouldBeDisabled() {
    return $this->disabled;
  }

}
