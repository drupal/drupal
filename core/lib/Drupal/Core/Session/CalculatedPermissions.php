<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\CacheableDependencyTrait;

/**
 * Represents a calculated set of permissions with cacheable metadata.
 *
 * @see \Drupal\core\Session\AccessPolicyProcessor
 */
class CalculatedPermissions implements CalculatedPermissionsInterface {

  use CacheableDependencyTrait;
  use CalculatedPermissionsTrait;

  /**
   * Constructs a new CalculatedPermissions.
   *
   * @param \Drupal\Core\Session\CalculatedPermissionsInterface $source
   *   The calculated permission to create a value object from.
   */
  public function __construct(CalculatedPermissionsInterface $source) {
    foreach ($source->getItems() as $item) {
      $this->items[$item->getScope()][$item->getIdentifier()] = $item;
    }
    $this->setCacheability($source);

    // The (persistent) cache contexts attached to the permissions are only
    // used internally to store the permissions in the VariationCache. We strip
    // these cache contexts when the calculated permissions get converted into a
    // value object here so that they will never bubble up by accident.
    $this->cacheContexts = [];
  }

}
