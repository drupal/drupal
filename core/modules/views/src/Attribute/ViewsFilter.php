<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;

/**
 * Defines a Plugin attribute class for views filter handlers.
 *
 * @see \Drupal\views\Plugin\views\filter\FilterPluginBase
 *
 * @ingroup views_filter_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsFilter extends PluginID {

}
