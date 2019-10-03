<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Contains all events emitted during the resource type build process.
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 */
final class ResourceTypeBuildEvents {

  /**
   * Emitted during the resource type build process.
   */
  const BUILD = 'jsonapi.resource_type.build';

}
