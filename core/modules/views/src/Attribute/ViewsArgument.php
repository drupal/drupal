<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a ViewsArgument attribute for plugin discovery.
 *
 * @see \Drupal\views\Plugin\views\argument\ArgumentPluginBase
 *
 * @ingroup views_argument_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsArgument extends Plugin {

}
