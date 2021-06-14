<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Specialization of a ResourceTypeField to represent a resource relationship.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 */
class ResourceTypeRelationship extends ResourceTypeField {

  /**
   * The resource type to which this relationships can relate.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType[]
   */
  protected $relatableResourceTypes;

  /**
   * Establishes the relatable resource types of this field.
   *
   * @param array $resource_types
   *   The array of relatable resource types.
   *
   * @return static
   *   A new instance of the field with the given relatable resource types.
   */
  public function withRelatableResourceTypes(array $resource_types) {
    $relationship = new static($this->internalName, $this->publicName, $this->enabled, $this->hasOne);
    $relationship->relatableResourceTypes = $resource_types;
    return $relationship;
  }

  /**
   * Gets the relatable resource types.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The resource type to which this relationships can relate.
   */
  public function getRelatableResourceTypes() {
    if (!isset($this->relatableResourceTypes)) {
      throw new \LogicException("withRelatableResourceTypes() must be called before getting relatable resource types.");
    }
    return $this->relatableResourceTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function withPublicName($public_name) {
    $relationship = parent::withPublicName($public_name);
    return isset($this->relatableResourceTypes)
      ? $relationship->withRelatableResourceTypes($this->relatableResourceTypes)
      : $relationship;
  }

  /**
   * {@inheritdoc}
   */
  public function disabled() {
    $relationship = parent::disabled();
    return isset($this->relatableResourceTypes)
      ? $relationship->withRelatableResourceTypes($this->relatableResourceTypes)
      : $relationship;
  }

}
