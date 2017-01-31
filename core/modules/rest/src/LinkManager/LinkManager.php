<?php

namespace Drupal\rest\LinkManager;

use Drupal\serialization\LinkManager\LinkManager as MovedLinkManager;

/**
 * @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0. This has
 *   been moved to the serialization module. This exists solely for BC.
 */
class LinkManager extends MovedLinkManager implements LinkManagerInterface {}
