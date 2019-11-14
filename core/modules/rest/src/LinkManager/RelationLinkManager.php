<?php

namespace Drupal\rest\LinkManager;

use Drupal\hal\LinkManager\RelationLinkManager as MovedLinkRelationManager;

/**
 * @deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. This has
 *   been moved to the hal module. This exists solely for BC.
 *
 * @see https://www.drupal.org/node/2830467
 */
class RelationLinkManager extends MovedLinkRelationManager implements RelationLinkManagerInterface {}
