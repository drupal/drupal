<?php

namespace Drupal\jsonapi\ResourceType;

use Drupal\Component\Assertion\Inspector;
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
   * The fields of the resource type to be built.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeField[]
   */
  protected $fields;

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
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeField[] $fields
   *   The fields of the resource type to be built.
   */
  protected function __construct($resource_type_name, array $fields) {
    assert(Inspector::assertAllObjects($fields, ResourceTypeField::class));
    $this->resourceTypeName = $resource_type_name;
    $this->fields = $fields;
  }

  /**
   * Creates a new ResourceTypeBuildEvent.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type for the resource type to be built.
   * @param string $bundle
   *   A bundle name for the resource type to be built. If the entity type does
   *   not have bundles, the entity type ID.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeField[] $fields
   *   The fields of the resource type to be built.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent
   *   A new event.
   */
  public static function createFromEntityTypeAndBundle(EntityTypeInterface $entity_type, $bundle, array $fields) {
    return new static(sprintf('%s--%s', $entity_type->id(), $bundle), $fields);
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

  /**
   * Gets the current fields of the resource type to be built.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceTypeField[]
   *   The current fields of the resource type to be built.
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Sets the public name of the given field on the resource type to be built.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeField $field
   *   The field for which to set a public name.
   * @param string $public_field_name
   *   The public field name to set.
   */
  public function setPublicFieldName(ResourceTypeField $field, $public_field_name) {
    foreach ($this->fields as $index => $value) {
      if ($field === $value) {
        $this->fields[$index] = $value->withPublicName($public_field_name);
        return;
      }
    }
  }

  /**
   * Disables the given field on the resource type to be built.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeField $field
   *   The field for which to set a public name.
   */
  public function disableField(ResourceTypeField $field) {
    foreach ($this->fields as $index => $value) {
      if ($field === $value) {
        $this->fields[$index] = $value->disabled();
        return;
      }
    }
  }

}
