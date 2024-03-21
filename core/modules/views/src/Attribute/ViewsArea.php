<?php

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;

/**
 * Defines a Plugin attribute object for views area handlers.
 *
 * @see \Drupal\views\Plugin\views\area\AreaPluginBase
 *
 * @ingroup views_area_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsArea extends PluginID {

}
