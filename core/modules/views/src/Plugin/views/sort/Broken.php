<?php

namespace Drupal\views\Plugin\views\sort;

use Drupal\views\Attribute\ViewsSort;
use Drupal\views\Plugin\views\BrokenHandlerTrait;

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_sort_handlers
 */
#[ViewsSort("broken")]
class Broken extends SortPluginBase {
  use BrokenHandlerTrait;

}
