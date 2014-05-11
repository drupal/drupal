<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityType.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Exception\EntityTypeIdLengthException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides an implementation of an entity type and its metadata.
 */
class EntityType implements EntityTypeInterface {

  use StringTranslationTrait;

  /**
   * Indicates whether entities should be statically cached.
   *
   * @var bool
   */
  protected $static_cache;

  /**
   * Indicates whether the rendered output of entities should be cached.
   *
   * @var bool
   */
  protected $render_cache;

  /**
   * Indicates if the persistent cache of field data should be used.
   *
   * @var bool
   */
  protected $field_cache;

  /**
   * An array of entity keys.
   *
   * @var array
   */
  protected $entity_keys = array();

  /**
   * The unique identifier of this entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The name of the provider of this entity type.
   *
   * @var string
   */
  protected $provider;

  /**
   * The name of the entity type class.
   *
   * @var string
   */
  protected $class;

  /**
   * An array of controllers.
   *
   * @var array
   */
  protected $controllers = array();

  /**
   * The name of the default administrative permission.
   *
   * @var string
   */
  protected $admin_permission;

  /**
   * The permission granularity level.
   *
   * The allowed values are respectively "entity_type", "bundle" or FALSE.
   *
   * @var string|bool
   */
  protected $permission_granularity;

  /**
   * Indicates whether fields can be attached to entities of this type.
   *
   * @var bool (optional)
   */
  protected $fieldable;

  /**
   * Link templates using the URI template syntax.
   *
   * @var array
   */
  protected $links = array();

  /**
   * The name of a callback that returns the label of the entity.
   *
   * @var string
   */
  protected $label_callback;

  /**
   * The name of the entity type which provides bundles.
   *
   * @var string
   */
  protected $bundle_entity_type;

  /**
   * The name of the entity type for which bundles are provided.
   *
   * @var string
   */
  protected $bundle_of;

  /**
   * The human-readable name of the entity bundles, e.g. Vocabulary.
   *
   * @var string
   */
  protected $bundle_label;

  /**
   * The name of the entity type's base table.
   *
   * @var string
   */
  protected $base_table;

  /**
   * The name of the entity type's revision data table.
   *
   * @var string
   */
  protected $revision_data_table;

  /**
   * The name of the entity type's revision table.
   *
   * @var string
   */
  protected $revision_table;

  /**
   * The name of the entity type's data table.
   *
   * @var string
   */
  protected $data_table;

  /**
   * Indicates whether entities of this type have multilingual support.
   *
   * @var bool
   */
  protected $translatable = FALSE;

  /**
   * The human-readable name of the type.
   *
   * @var string
   */
  protected $label;

  /**
   * A callable that can be used to provide the entity URI.
   *
   * @var callable
   */
  protected $uri_callback;

  /**
   * The machine name of the entity type group.
   */
  protected $group;

  /**
   * The human-readable name of the entity type group.
   */
  protected $group_label;

  /**
   * Constructs a new EntityType.
   *
   * @param array $definition
   *   An array of values from the annotation.
   *
   * @throws \Drupal\Core\Entity\Exception\EntityTypeIdLengthException
   *   Thrown when attempting to instantiate an entity type with too long ID.
   */
  public function __construct($definition) {
    // Throw an exception if the entity type ID is longer than 32 characters.
    if (Unicode::strlen($definition['id']) > static::ID_MAX_LENGTH) {
      throw new EntityTypeIdLengthException(String::format(
        'Attempt to create an entity type with an ID longer than @max characters: @id.', array(
          '@max' => static::ID_MAX_LENGTH,
          '@id' => $definition['id'],
        )
      ));
    }

    foreach ($definition as $property => $value) {
      $this->{$property} = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($property) {
    return isset($this->{$property}) ? $this->{$property} : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    $this->{$property} = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isStaticallyCacheable() {
    return isset($this->static_cache) ? $this->static_cache: TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderCacheable() {
    return isset($this->render_cache) ? $this->render_cache: TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldDataCacheable() {
    return isset($this->field_cache) ? $this->field_cache: TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys() {
    return $this->entity_keys + array('revision' => '', 'bundle' => '');
  }

  /**
   * {@inheritdoc}
   */
  public function getKey($key) {
    $keys = $this->getKeys();
    return isset($keys[$key]) ? $keys[$key] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasKey($key) {
    $keys = $this->getKeys();
    return !empty($keys[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->class = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubclassOf($class) {
    return is_subclass_of($this->getClass(), $class);
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerClasses() {
    return $this->controllers + array(
      'access' => 'Drupal\Core\Entity\EntityAccessController',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerClass($controller_type, $nested = FALSE) {
    if ($this->hasControllerClass($controller_type, $nested)) {
      $controllers = $this->getControllerClasses();
      return $nested ? $controllers[$controller_type][$nested] : $controllers[$controller_type];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setControllerClass($controller_type, $value) {
    $this->controllers[$controller_type] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasControllerClass($controller_type, $nested = FALSE) {
    $controllers = $this->getControllerClasses();
    if (!isset($controllers[$controller_type]) || ($nested && !isset($controllers[$controller_type][$nested]))) {
      return FALSE;
    }
    $controller = $controllers[$controller_type];
    if ($nested) {
      $controller = $controller[$nested];
    }
    return class_exists($controller);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return $this->getControllerClass('storage');
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageClass($class) {
    $this->controllers['storage'] = $class;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation) {
    return $this->getControllerClass('form', $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function setFormClass($operation, $class) {
    $this->controllers['form'][$operation] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFormClasses() {
    return !empty($this->controllers['form']);
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilderClass() {
    return $this->getControllerClass('list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function setListBuilderClass($class) {
    $this->controllers['list_builder'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasListBuilderClass() {
    return $this->hasControllerClass('list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilderClass() {
    return $this->getControllerClass('view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function setViewBuilderClass($class) {
    $this->controllers['view_builder'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewBuilderClass() {
    return $this->hasControllerClass('view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessClass() {
    return $this->getControllerClass('access');
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessClass($class) {
    $this->controllers['access'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->admin_permission ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionGranularity() {
    return isset($this->permission_granularity) ? $this->permission_granularity : 'entity_type';
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldable() {
    return isset($this->fieldable) ? $this->fieldable : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTemplates() {
    return isset($this->links) ? $this->links : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTemplate($key) {
    $links = $this->getLinkTemplates();
    return isset($links[$key]) ? $links[$key] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    $links = $this->getLinkTemplates();
    return isset($links[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkTemplate($key, $route_name) {
    $this->links[$key] = $route_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelCallback() {
    return isset($this->label_callback) ? $this->label_callback : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelCallback($callback) {
    $this->label_callback = $callback;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLabelCallback() {
    return isset($this->label_callback);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleEntityType() {
    return isset($this->bundle_entity_type) ? $this->bundle_entity_type : 'bundle';
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleOf() {
    return isset($this->bundle_of) ? $this->bundle_of : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    return isset($this->bundle_label) ? $this->bundle_label : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return isset($this->base_table) ? $this->base_table : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return !empty($this->translatable);
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionable() {
    // Entity types are revisionable if a revision key has been specified.
    return $this->hasKey('revision');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDataTable() {
    return isset($this->revision_data_table) ? $this->revision_data_table : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionTable() {
    return isset($this->revision_table) ? $this->revision_table : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTable() {
    return isset($this->data_table) ? $this->data_table : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->label) ? $this->label : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLowercaseLabel() {
    return Unicode::strtolower($this->getLabel());
  }

  /**
   * {@inheritdoc}
   */
  public function getUriCallback() {
    return isset($this->uri_callback) ? $this->uri_callback : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setUriCallback($callback) {
    $this->uri_callback = $callback;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }


  /**
   * {@inheritdoc}
   */
  public function getGroupLabel() {
    return !empty($this->group_label) ? $this->group_label : $this->t('Other', array(), array('context' => 'Entity type group'));
  }

}
