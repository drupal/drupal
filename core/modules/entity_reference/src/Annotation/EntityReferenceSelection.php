<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Annotation\EntityReferenceSelection.
 */

namespace Drupal\entity_reference\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an EntityReferenceSelection plugin annotation object.
 *
 * Plugin Namespace: Plugin\entity_reference\selection
 *
 * For a working example, see
 * \Drupal\comment\Plugin\entity_reference\selection\CommentSelection
 *
 * @see \Drupal\entity_reference\Plugin\Type\SelectionPluginManager
 * @see \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface
 * @see \Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase
 * @see \Drupal\entity_reference\Plugin\Derivative\SelectionBase
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
  public $entity_types = array();

  /**
   * The weight of the plugin in it's group.
   *
   * @var int
   */
  public $weight;

}
