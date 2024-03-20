<?php

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;

/**
 * Defines a Plugin attribute object for views join plugins.
 *
 * @see \Drupal\views\Plugin\views\join\JoinPluginBase
 *
 * @ingroup views_join_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsJoin extends PluginID {

}
