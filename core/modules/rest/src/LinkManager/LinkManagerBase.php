<?php

namespace Drupal\rest\LinkManager;

use Drupal\hal\LinkManager\LinkManagerBase as MovedLinkManagerBase;

/**
 * @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0. This has
 *   been moved to the hal module. This exists solely for BC.
 *
 * @see https://www.drupal.org/node/2830467
 */
abstract class LinkManagerBase extends MovedLinkManagerBase {}
