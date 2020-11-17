<?php

namespace Drupal\jsonapi_test_resource_type_aliasing\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceType as ResourceTypeBase;

/**
 * Resource type with uses an alternative type name.
 *
 * @todo remove this class in https://www.drupal.org/project/drupal/issues/3105318
 */
class AliasedResourceType extends ResourceTypeBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ResourceType $resource_type, string $alias) {
    parent::__construct(
      $resource_type->getEntityTypeId(),
      $resource_type->getBundle(),
      $resource_type->getDeserializationTargetClass(),
      $resource_type->isInternal(),
      $resource_type->isLocatable(),
      $resource_type->isMutable(),
      $resource_type->isVersionable(),
      $resource_type->getFields()
    );
    // Alias the resource type name with an alternative pattern.
    $this->typeName = $alias;
  }

}
