<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Manages entity type plugin definitions.
 *
 * Each entity type definition array is set in the entity type plugin's
 * annotation and altered by hook_entity_info_alter(). The definition includes
 * the following keys:
 * - module: The name of the module providing the type.
 * - class: The name of the entity type class. Defaults to
 *   Drupal\Core\Entity\Entity.
 * - base_table: The name of the entity type's base table. Used by
 *   Drupal\Core\Entity\DatabaseStorageController.
 * - controller_class: The name of the class that is used to load the objects.
 *   The class must implement
 *   Drupal\Core\Entity\EntityStorageControllerInterface. Defaults to
 *   Drupal\Core\Entity\DatabaseStorageController.
 * - fieldable: (optional) Boolean indicating whether fields can be attached
 *   to entities of this type. Defaults to FALSE.
 * - field_cache: (optional) Boolean indicating whether the Field API's
 *   Field API's persistent cache of field data should be used. The persistent
 *   cache should usually only be disabled if a higher level persistent cache
 *   is available for the entity type. Defaults to TRUE.
 * - form_controller_class: (optional) An associative array where the keys
 *   are the names of the different form operations (such as 'create',
 *   'edit', or 'delete') and the values are the names of the controller
 *   classes for those operations. The name of the operation is passed also
 *   to the form controller's constructor, so that one class can be used for
 *   multiple entity forms when the forms are similar. Defaults to
 *   Drupal\Core\Entity\EntityFormController.
 * - label: The human-readable name of the type.
 * - bundle_label: The human-readable name of the entity bundles, e.g.
 *   Vocabulary.
 * - label_callback: (optional) A function taking an entity and optional
 *   langcode argument, and returning the label of the entity. If langcode is
 *   omitted, the entity's default language is used.
 *   The entity label is the main string associated with an entity; for
 *   example, the title of a node or the subject of a comment. If there is an
 *   entity object property that defines the label, use the 'label' element
 *   of the 'entity_keys' return value component to provide this information
 *   (see below). If more complex logic is needed to determine the label of
 *   an entity, you can instead specify a callback function here, which will
 *   be called to determine the entity label. See also the
 *   Drupal\Core\Entity\Entity::label() method, which implements this logic.
 * - list_controller_class: (optional) The name of the class that provides
 *   listings of the entities. The class must implement
 *   Drupal\Core\Entity\EntityListControllerInterface. Defaults to
 *   Drupal\Core\Entity\EntityListController.
 * - render_controller_class: The name of the class that is used to render the
 *   entities. Defaults to Drupal\Core\Entity\EntityRenderController.
 * - access_controller_class: The name of the class that is used for access
 *   checks. The class must implement
 *   Drupal\Core\Entity\EntityAccessControllerInterface. Defaults to
 *   Drupal\Core\Entity\EntityAccessController.
 * - translation_controller_class: (optional) The name of the translation
 *   controller class that should be used to handle the translation process.
 *   See Drupal\translation_entity\EntityTranslationControllerInterface for more
 *   information.
 * - static_cache: (optional) Boolean indicating whether entities should be
 *   statically cached during a page request. Used by
 *   Drupal\Core\Entity\DatabaseStorageController. Defaults to TRUE.
 * - translation: (optional) An associative array of modules registered as
 *   field translation handlers. Array keys are the module names, and array
 *   values can be any data structure the module uses to provide field
 *   translation. If the value is empty, the module will not be used as a
 *   translation handler.
 * - entity_keys: An array describing how the Field API can extract certain
 *   information from objects of this entity type. Elements:
 *   - id: The name of the property that contains the primary ID of the
 *     entity. Every entity object passed to the Field API must have this
 *     property and its value must be numeric.
 *   - revision: (optional) The name of the property that contains the
 *     revision ID of the entity. The Field API assumes that all revision IDs
 *     are unique across all entities of a type. This entry can be omitted if
 *     the entities of this type are not versionable.
 *   - bundle: (optional) The name of the property that contains the bundle
 *     name for the entity. The bundle name defines which set of fields are
 *     attached to the entity (e.g. what nodes call "content type"). This
 *     entry can be omitted if this entity type exposes a single bundle (such
 *     that all entities have the same collection of fields). The name of
 *     this single bundle will be the same as the entity type.
 *   - label: The name of the property that contains the entity label. For
 *     example, if the entity's label is located in $entity->subject, then
 *     'subject' should be specified here. If complex logic is required to
 *     build the label, a 'label_callback' should be defined instead (see
 *     the 'label_callback' section above for details).
 *   - uuid (optional): The name of the property that contains the universally
 *     unique identifier of the entity, which is used to distinctly identify
 *     an entity across different systems.
 * - bundle_keys: An array describing how the Field API can extract the
 *   information it needs from the bundle objects for this type (e.g
 *   Vocabulary objects for terms; not applicable for nodes). This entry can
 *   be omitted if this type's bundles do not exist as standalone objects.
 *   Elements:
 *   - bundle: The name of the property that contains the name of the bundle
 *     object.
 * - menu_base_path: (optional) The base menu router path to which the entity
 *   administration user interface responds. It can be used to generate UI
 *   links and to attach additional router items to the entity UI in a generic
 *   fashion.
 * - menu_view_path: (optional) The menu router path to be used to view the
 *   entity.
 * - menu_edit_path: (optional) The menu router path to be used to edit the
 *   entity.
 * - menu_path_wildcard: (optional) A string identifying the menu loader in the
 *   router path.
 * - permission_granularity: (optional) Specifies whether a module exposing
 *   permissions for the current entity type should use entity-type level
 *   granularity, bundle level granularity or just skip this entity. The allowed
 *   values are respectively "entity_type", "bundle" or FALSE. Defaults to
 *   "entity_type".
 *
 * The defaults for the plugin definition are provided in
 * \Drupal\Core\Entity\EntityManager::defaults.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see entity_get_info()
 * @see hook_entity_info_alter()
 */
class EntityManager extends PluginManagerBase {

  /**
   * Contains instantiated controllers keyed by controller type and entity type.
   *
   * @var array
   */
  protected $controllers = array();

  /**
   * The default values for optional keys of the entity plugin definition.
   *
   * @var array
   */
  protected $defaults = array(
    'class' => 'Drupal\Core\Entity\Entity',
    'controller_class' => 'Drupal\Core\Entity\DatabaseStorageController',
    'entity_keys' => array(
      'revision' => '',
      'bundle' => '',
    ),
    'fieldable' => FALSE,
    'field_cache' => TRUE,
    'form_controller_class' => array(
      'default' => 'Drupal\Core\Entity\EntityFormController',
    ),
    'list_controller_class' => 'Drupal\Core\Entity\EntityListController',
    'access_controller_class' => 'Drupal\Core\Entity\EntityAccessController',
    'static_cache' => TRUE,
    'translation' => array(),
    'permission_granularity' => 'entity_type',
  );

  /**
   * Constructs a new Entity plugin manager.
   *
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct(array $namespaces) {
    // Allow the plugin definition to be altered by hook_entity_info_alter().
    $this->discovery = new AnnotatedClassDiscovery('Core', 'Entity', $namespaces);
    $this->discovery = new InfoHookDecorator($this->discovery, 'entity_info');
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'entity_info');
    $this->discovery = new CacheDecorator($this->discovery, 'entity_info:' . language(LANGUAGE_TYPE_INTERFACE)->langcode, 'cache', CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));

    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Prepare entity schema fields SQL info for
    // Drupal\Core\Entity\DatabaseStorageControllerInterface::buildQuery().
    if (isset($definition['base_table'])) {
      $definition['schema_fields_sql']['base_table'] = drupal_schema_fields_sql($definition['base_table']);
      if (isset($definition['data_table'])) {
        $definition['schema_fields_sql']['data_table'] = drupal_schema_fields_sql($definition['data_table']);
      }
      if (isset($definition['revision_table'])) {
        $definition['schema_fields_sql']['revision_table'] = drupal_schema_fields_sql($definition['revision_table']);
      }
    }
  }

  /**
   * Returns an entity controller class.
   *
   * @param string $entity_type
   *   The name of the entity type
   * @param string $controller_type
   *   The name of the controller.
   * @param string|null $nested
   *   (optional) If this controller definition is nested, the name of the key.
   *   Defaults to NULL.
   *
   * @return string
   *   The class name for this controller instance.
   */
  public function getControllerClass($entity_type, $controller_type, $nested = NULL) {
    $definition = $this->getDefinition($entity_type);
    if (empty($definition[$controller_type])) {
      throw new \InvalidArgumentException(sprintf('The entity (%s) did not specify a %s.', $entity_type, $controller_type));
    }

    $class = $definition[$controller_type];

    // Some class definitions can be nested.
    if (isset($nested)) {
      if (empty($class[$nested])) {
        throw new \InvalidArgumentException(sprintf("Missing '%s: %s' for entity '%s'", $controller_type, $nested, $entity_type));
      }

      $class = $class[$nested];
    }

    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Entity (%s) %s "%s" does not exist.', $entity_type, $controller_type, $class));
    }

    return $class;
  }

  /**
   * Creates a new storage controller instance.
   *
   * @param string $entity_type
   *   The entity type for this storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageControllerInterface
   *   A storage controller instance.
   */
  public function getStorageController($entity_type) {
    if (!isset($this->controllers['storage'][$entity_type])) {
      $class = $this->getControllerClass($entity_type, 'controller_class');
      $this->controllers['storage'][$entity_type] = new $class($entity_type);
    }
    return $this->controllers['storage'][$entity_type];
  }

  /**
   * Creates a new list controller instance.
   *
   * @param string $entity_type
   *   The entity type for this list controller.
   *
   * @return \Drupal\Core\Entity\EntityListControllerInterface
   *   A list controller instance.
   */
  public function getListController($entity_type) {
    if (!isset($this->controllers['listing'][$entity_type])) {
      $class = $this->getControllerClass($entity_type, 'list_controller_class');
      $this->controllers['listing'][$entity_type] = new $class($entity_type, $this->getStorageController($entity_type));
    }
    return $this->controllers['listing'][$entity_type];
  }

  /**
   * Creates a new form controller instance.
   *
   * @param string $entity_type
   *   The entity type for this form controller.
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return \Drupal\Core\Entity\EntityFormControllerInterface
   *   A form controller instance.
   */
  public function getFormController($entity_type, $operation) {
    if (!isset($this->controllers['form'][$operation][$entity_type])) {
      $class = $this->getControllerClass($entity_type, 'form_controller_class', $operation);
      $this->controllers['form'][$operation][$entity_type] = new $class($operation);
    }
    return $this->controllers['form'][$operation][$entity_type];
  }

  /**
   * Creates a new render controller instance.
   *
   * @param string $entity_type
   *   The entity type for this render controller.
   *
   * @return \Drupal\Core\Entity\EntityRenderControllerInterface.
   *   A render controller instance.
   */
  public function getRenderController($entity_type) {
    if (!isset($this->controllers['render'][$entity_type])) {
      $class = $this->getControllerClass($entity_type, 'render_controller_class');
      $this->controllers['render'][$entity_type] = new $class($entity_type);
    }
    return $this->controllers['render'][$entity_type];
  }

  /**
   * Creates a new access controller instance.
   *
   * @param string $entity_type
   *   The entity type for this access controller.
   *
   * @return \Drupal\Core\Entity\EntityRenderControllerInterface.
   *   A access controller instance.
   */
  public function getAccessController($entity_type) {
    if (!isset($this->controllers['access'][$entity_type])) {
      $class = $this->getControllerClass($entity_type, 'access_controller_class');
      $this->controllers['access'][$entity_type] = new $class($entity_type);
    }
    return $this->controllers['access'][$entity_type];
  }

}
