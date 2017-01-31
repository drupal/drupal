<?php

namespace Drupal\rest\LinkManager;

use Drupal\serialization\LinkManager\ConfigurableLinkManagerInterface as MovedConfigurableLinkManagerInterface;

/**
 * @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0. This has
 *   been moved to the serialization module. This exists solely for BC.
 */
interface ConfigurableLinkManagerInterface extends MovedConfigurableLinkManagerInterface {}
