<?php

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;

/**
 * Defines a Plugin attribute object for views sort handlers.
 *
 * @see \Drupal\views\Plugin\views\sort\SortPluginBase
 *
 * @ingroup views_sort_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsSort extends PluginID {

}
