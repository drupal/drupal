<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Specialization of a ResourceTypeField to represent a resource type attribute.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 */
class ResourceTypeAttribute extends ResourceTypeField {}
