<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;

/**
 * Defines a Plugin attribute class for views relationship handlers.
 *
 * @see \Drupal\views\Plugin\views\relationship\RelationshipPluginBase
 *
 * @ingroup views_relationship_handlers
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsRelationship extends PluginID {

}
