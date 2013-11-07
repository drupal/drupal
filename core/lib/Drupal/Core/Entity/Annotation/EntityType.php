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
   *   class must implement \Drupal\Core\Entity\EntityViewBuilderInterface.
   * - access: The name of the class that is used for access checks. The class
   *   must implement \Drupal\Core\Entity\EntityAccessControllerInterface.
   *   Defaults to \Drupal\Core\Entity\EntityAccessController.
   * - translation: The name of the controller class that should be used to
   *   handle the translation process. The class must implement
   *   \Drupal\content_translation\ContentTranslationControllerInterface.
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
   * The name of the default administrative permission.
   *
   * The default \Drupal\Core\Entity\EntityAccessController class checks this
   * permission for all operations in its checkAccess() method. Entities with
   * more complex permissions can extend this class to do their own access
   * checks.
   *
   * @var string (optional)
   */
  public $admin_permission;

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
   * Boolean indicating whether the rendered output of entities should be
   * cached.
   *
   * @var bool (optional)
   */
  public $render_cache = TRUE;

  /**
   * Boolean indicating whether entities of this type have multilingual support.
   *
   * At an entity level, this indicates language support and at a bundle level
   * this indicates translation support.
   *
   * @var bool (optional)
   */
  public $translatable = FALSE;

  /**
   * @todo content_translation_entity_info_alter() uses this but it is undocumented.
   *
   * @var array
   */
  public $translation = array();

  /**
   * The name of the entity type for which bundles are provided.
   *
   * It can be used by other modules to act accordingly; for example,
   * the Field UI module uses it to add operation links to manage fields and
   * displays.
   *
   * @var string
   */
  public $bundle_of;

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
   * Link templates using the URI template syntax.
   *
   * Links are an array of standard link relations to the URI template that
   * should be used for them. Where possible, link relationships should use
   * established IANA relationships rather than custom relationships.
   *
   * Every entity type should, at minimum, define "canonical", which is the
   * pattern for URIs to that entity. Even if the entity will have no HTML page
   * exposed to users it should still have a canonical URI in order to be
   * compatible with web services. Entities that will be user-editable via an
   * HTML page must also define an "edit-form" relationship.
   *
   * By default, the following placeholders are supported:
   * - entityType: The machine name of the entity type.
   * - bundle: The bundle machine name of the entity.
   * - id: The unique ID of the entity.
   * - uuid: The UUID of the entity.
   * - [entityType]: The entity type itself will also be a valid token for the
   *   ID of the entity. For instance, a placeholder of {node} used on the Node
   *   class would have the same value as {id}. This is generally preferred
   *   over "id" for better self-documentation.
   *
   * Specific entity types may also expand upon this list by overriding the
   * uriPlaceholderReplacements() method.
   *
   * @link http://www.iana.org/assignments/link-relations/link-relations.xml @endlink
   * @link http://tools.ietf.org/html/rfc6570 @endlink
   *
   * @var array
   */
  public $links = array(
    'canonical' => '/entity/{entityType}/{id}',
  );

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
