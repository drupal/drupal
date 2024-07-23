<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * No matching route was found.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is no
 *  replacement.
 *
 * @see https://www.drupal.org/node/3462776
 */
class MatchingRouteNotFoundException extends ResourceNotFoundException {
}
