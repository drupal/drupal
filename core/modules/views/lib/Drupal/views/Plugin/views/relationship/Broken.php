<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\relationship\Broken.
 */

namespace Drupal\views\Plugin\views\relationship;

use Drupal\views\Plugin\views\BrokenHandlerTrait;

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_relationship_handlers
 *
 * @PluginID("broken")
 */
class Broken extends RelationshipPluginBase {
  use BrokenHandlerTrait;

}
