<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\Broken.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Plugin\views\BrokenHandlerTrait;

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("broken")
 */
class Broken extends ArgumentPluginBase {
  use BrokenHandlerTrait;

}
