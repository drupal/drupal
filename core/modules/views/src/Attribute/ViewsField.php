<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;

/**
 * Defines a Plugin attribute class for views field handlers.
 *
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 *
 * @ingroup views_field_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsField extends PluginID {

}
