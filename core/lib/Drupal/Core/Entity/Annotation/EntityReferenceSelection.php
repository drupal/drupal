<?php

namespace Drupal\Core\Entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an EntityReferenceSelection plugin annotation object.
 *
 * Plugin Namespace: Plugin\EntityReferenceSelection
 *
 * For a working example, see
 * \Drupal\comment\Plugin\EntityReferenceSelection\CommentSelection
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see plugin_api
 *
 * @Annotation
 */
class EntityReferenceSelection extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the selection plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The selection plugin group.
   *
   * This property is used to allow selection plugins to target a specific
   * entity type while also inheriting the code of an existing selection plugin.
   * For example, if we want to override the NodeSelection from the 'default'
   * selection type, we can define the annotation of a new plugin as follows:
   * @code
   * id = "node_advanced",
   * entity_types = {"node"},
   * group = "default",
   * weight = 5
   * @endcode
   *
   * @var string
   */
  public $group;

  /**
   * An array of entity types that can be referenced by this plugin. Defaults to
   * all entity types.
   *
   * @var array (optional)
   */
  public $entity_types = [];

  /**
   * The weight of the plugin in its group.
   *
   * @var int
   */
  public $weight;

}
