<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Access\AccessInterface.
 */

namespace Drupal\Core\Routing\Access;

use Drupal\Core\Access\AccessInterface as GenericAccessInterface;

/**
 * An access check service determines access rules for particular routes.
 */
interface AccessInterface extends GenericAccessInterface {

  // @todo Remove this interface since it no longer defines any methods?
  // @see https://drupal.org/node/2266817.

}
