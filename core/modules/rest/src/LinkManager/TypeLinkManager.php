<?php

namespace Drupal\rest\LinkManager;

use Drupal\hal\LinkManager\TypeLinkManager as MovedTypeLinkManager;

/**
 * @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0. This has
 *   been moved to the hal module. This exists solely for BC.
 */
class TypeLinkManager extends MovedTypeLinkManager implements TypeLinkManagerInterface {}
