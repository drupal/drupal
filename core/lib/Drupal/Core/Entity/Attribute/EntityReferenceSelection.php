<?php

namespace Drupal\Core\Entity\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an EntityReferenceSelection attribute for plugin discovery.
 *
 * Plugin Namespace: Plugin\EntityReferenceSelection
 *
 * For a working example, see
 * \Drupal\comment\Plugin\EntityReferenceSelection\CommentSelection
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EntityReferenceSelection extends Plugin {

  /**
   * Constructs an EntityReferenceSelection attribute.
   *
   * @param string $id
   *   The plugin ID. There are some implementation bugs that make the plugin
   *   available only if the ID follows a specific pattern. It must be either
   *   identical to group or prefixed with the group. E.g. if the group is "foo"
   *   the ID must be either "foo" or "foo:bar".
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the selection plugin.
   * @param string $group
   *   The selection plugin group. This property is used to allow selection
   *   plugins to target a specific entity type while also inheriting the code
   *   of an existing selection plugin. For example, if we want to override the
   *   NodeSelection from the 'default' selection type, we can define the
   *   attribute as follows:
   *   @code
   *   #[EntityReferenceSelection(
   *     id: "default:node_advanced",
   *     entity_types: ["node"],
   *     group: "default",
   *     weight: 5
   *   )]
   *   @endcode
   * @param int $weight
   *   The weight of the plugin in its group. This property is used to select
   *   the "best" plugin within a group.
   * @param string[] $entity_types
   *   (optional) An array of entity types that can be referenced by this
   *   plugin. Defaults to all entity types.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly string $group,
    public readonly int $weight,
    public readonly array $entity_types = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
