<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_collection_count\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Subclass with overridden ::includeCount() for testing purposes.
 */
class CountableResourceType extends ResourceType {

  /**
   * {@inheritdoc}
   */
  public function includeCount() {
    return TRUE;
  }

}
