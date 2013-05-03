<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Annotation\EntityType.
 */

namespace Drupal\Core\Entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Entity type annotation object.
 *
 * @Annotation
 */
class EntityType extends Plugin {

  /**
   * The name of the module providing the type.
   *
   * @var string
   */
  public $module;

  /**
   * The name of the entity type class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

  /**
   * The name of the entity type's base table.
   *
   * @todo This is only used by \Drupal\Core\Entity\DatabaseStorageController.
   *
   * @var string
   */
  public $base_table;

  /**
   * An associative array where the keys are the names of different controller
   * types (listed below) and the values are the names of the classes that
   * implement that controller:
   * - storage: The name of the class that is used to load the objects. The
   *   class must implement \Drupal\Core\Entity\EntityStorageControllerInterface.
   * - form: An associative array where the keys are the names of the different
   *   form operations (such as 'create', 'edit', or 'delete') and the values
   *   are the names of the controller classes for those operations. The name of
   *   the operation is passed also to the form controller's constructor, so
   *   that one class can be used for multiple entity forms when the forms are
   *   similar. The classes must implement
   *   \Drupal\Core\Entity\EntityFormControllerInterface
   * - list: The name of the class that provides listings of the entities. The
   *   class must implement \Drupal\Core\Entity\EntityListControllerInterface.
   * - render: The name of the class that is used to render the entities. The
   *   class must implement \Drupal\Core\Entity\EntityRenderControllerInterface.
   * - access: The name of the class that is used for access checks. The class
   *   must implement \Drupal\Core\Entity\EntityAccessControllerInterface.
   *   Defaults to \Drupal\Core\Entity\EntityAccessController.
   * - translation: The name of the controller class that should be used to
   *   handle the translation process. The class must implement
   *   \Drupal\translation_entity\EntityTranslationControllerInterface.
   *
   * @todo Interfaces from outside \Drupal\Core or \Drupal\Component should not
   *   be used here.
   *
   * @var array
   */
  public $controllers = array(
    'access' => 'Drupal\Core\Entity\EntityAccessController',
  );

  /**
   * Boolean indicating whether fields can be attached to entities of this type.
   *
   * @var bool (optional)
   */
  public $fieldable = FALSE;

  /**
   * Boolean indicating if the persistent cache of field data should be used.
   *
   * The persistent cache should usually only be disabled if a higher level
   * persistent cache is available for the entity type. Defaults to TRUE.
   *
   * @var bool (optional)
   */
  public $field_cache = TRUE;

  /**
   * The human-readable name of the type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The human-readable name of the entity bundles, e.g. Vocabulary.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $bundle_label;

  /**
   * The name of a function that returns the label of the entity.
   *
   * The function takes an entity and optional langcode argument, and returns
   * the label of the entity. If langcode is omitted, the entity's default
   * language is used. The entity label is the main string associated with an
   * entity; for example, the title of a node or the subject of a comment. If
   * there is an entity object property that defines the label, use the 'label'
   * element of the 'entity_keys' return value component to provide this
   * information (see below). If more complex logic is needed to determine the
   * label of an entity, you can instead specify a callback function here, which
   * will be called to determine the entity label. See also the
   * \Drupal\Core\Entity\EntityInterface::label() method, which implements this
   * logic.
   *
   * @var string (optional)
   */
  public $label_callback;

  /**
   * Boolean indicating whether entities should be statically cached during a page request.
   *
   * @todo This is only used by \Drupal\Core\Entity\DatabaseStorageController.
   *
   * @var bool (optional)
   */
  public $static_cache = TRUE;

  /**
   * Boolean indicating whether entities of this type have mutlilingual support.
   *
   * @var bool (optional)
   */
  public $translatable = FALSE;

  /**
   * @todo translation_entity_entity_info_alter() uses this but it is undocumented.
   *
   * @var array
   */
  public $translation = array();

  /**
   * An array describing how the Field API can extract certain information from
   * objects of this entity type:
   * - id: The name of the property that contains the primary ID of the entity.
   *   Every entity object passed to the Field API must have this property and
   *   its value must be numeric.
   * - revision: (optional) The name of the property that contains the revision
   *   ID of the entity. The Field API assumes that all revision IDs are unique
   *   across all entities of a type. This entry can be omitted if the entities
   *   of this type are not versionable.
   * - bundle: (optional) The name of the property that contains the bundle name
   *   for the entity. The bundle name defines which set of fields are attached
   *   to the entity (e.g. what nodes call "content type"). This entry can be
   *   omitted if this entity type exposes a single bundle (such that all
   *   entities have the same collection of fields). The name of this single
   *   bundle will be the same as the entity type.
   * - label: The name of the property that contains the entity label. For
   *   example, if the entity's label is located in $entity->subject, then
   *   'subject' should be specified here. If complex logic is required to build
   *   the label, a 'label_callback' should be defined instead (see the
   *   $label_callback block above for details).
   * - uuid (optional): The name of the property that contains the universally
   *   unique identifier of the entity, which is used to distinctly identify an
   *   entity across different systems.
   *
   * @var array
   */
  public $entity_keys = array(
    'revision' => '',
    'bundle' => '',
  );

  /**
   * An array describing how the Field API can extract the information it needs
   * from the bundle objects for this type (e.g Vocabulary objects for terms;
   * not applicable for nodes):
   * - bundle: The name of the property that contains the name of the bundle
   *   object.
   *
   * This entry can be omitted if this type's bundles do not exist as standalone
   * objects.
   *
   * @var array
   */
  public $bundle_keys;

  /**
   * The base router path for the entity type's field administration page.
   *
   * If the entity type has a bundle, include {bundle} in the path.
   *
   * For example, the node entity type specifies
   * "admin/structure/types/manage/{bundle}" as its base field admin path.
   *
   * @var string (optional)
   */
  public $route_base_path;

  /**
   * The prefix for the bundles of this entity type.
   *
   * For example, the comment bundle is prefixed with 'comment_node_'.
   *
   * @var string (optional)
   */
  public $bundle_prefix;

  /**
   * The base menu router path to which the entity admin user interface responds.
   *
   * It can be used to generate UI links and to attach additional router items
   * to the entity UI in a generic fashion.
   *
   * @var string (optional)
   */
  public $menu_base_path;

  /**
   * The menu router path to be used to view the entity.
   *
   * @var string (optional)
   */
  public $menu_view_path;

  /**
   * The menu router path to be used to edit the entity.
   *
   * @var string (optional)
   */
  public $menu_edit_path;

  /**
   * A string identifying the menu loader in the router path.
   *
   * @var string (optional)
   */
  public $menu_path_wildcard;

  /**
   * Specifies whether a module exposing permissions for the current entity type
   * should use entity-type level granularity, bundle level granularity or just
   * skip this entity. The allowed values are respectively "entity_type",
   * "bundle" or FALSE.
   *
   * @var string|bool (optional)
   */
  public $permission_granularity = 'entity_type';

}
