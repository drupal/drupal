<?php

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Exception\EntityTypeIdLengthException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an implementation of an entity type and its metadata.
 *
 * @ingroup entity_api
 */
class EntityType extends PluginDefinition implements EntityTypeInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Indicates whether entities should be statically cached.
   *
   * @var bool
   */
  protected $static_cache = TRUE;

  /**
   * Indicates whether the rendered output of entities should be cached.
   *
   * @var bool
   */
  protected $render_cache = TRUE;

  /**
   * Indicates if the persistent cache of field data should be used.
   *
   * @var bool
   */
  protected $persistent_cache = TRUE;

  /**
   * An array of entity keys.
   *
   * @var array
   */
  protected $entity_keys = [];

  /**
   * The unique identifier of this entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The name of the original entity type class.
   *
   * This is only set if the class name is changed.
   *
   * @var string
   */
  protected $originalClass;

  /**
   * An array of handlers.
   *
   * @var array
   */
  protected $handlers = [];

  /**
   * The name of the default administrative permission.
   *
   * @var string
   */
  protected $admin_permission;

  /**
   * The permission granularity level.
   *
   * The allowed values are respectively "entity_type" or "bundle".
   *
   * @var string
   */
  protected $permission_granularity = 'entity_type';
  /**
   * Link templates using the URI template syntax.
   *
   * @var array
   */
  protected $links = [];

  /**
   * The name of the entity type which provides bundles.
   *
   * @var string
   */
  protected $bundle_entity_type = NULL;

  /**
   * The name of the entity type for which bundles are provided.
   *
   * @var string|null
   */
  protected $bundle_of = NULL;

  /**
   * The human-readable name of the entity bundles, e.g. Vocabulary.
   *
   * @var string|null
   */
  protected $bundle_label = NULL;

  /**
   * The name of the entity type's base table.
   *
   * @var string|null
   */
  protected $base_table = NULL;

  /**
   * The name of the entity type's revision data table.
   *
   * @var string|null
   */
  protected $revision_data_table = NULL;

  /**
   * The name of the entity type's revision table.
   *
   * @var string|null
   */
  protected $revision_table = NULL;

  /**
   * The name of the entity type's data table.
   *
   * @var string|null
   */
  protected $data_table = NULL;

  /**
   * Indicates whether the entity data is internal.
   *
   * @var bool
   */
  protected $internal = FALSE;

  /**
   * Indicates whether entities of this type have multilingual support.
   *
   * @var bool
   */
  protected $translatable = FALSE;

  /**
   * Indicates whether the revision form fields should be added to the form.
   *
   * @var bool
   */
  protected $show_revision_ui = FALSE;

  /**
   * The human-readable name of the type.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getLabel()
   */
  protected $label = '';

  /**
   * The human-readable label for a collection of entities of the type.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getCollectionLabel()
   */
  protected $label_collection = '';

  /**
   * The indefinite singular name of the type.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getSingularLabel()
   */
  protected $label_singular = '';

  /**
   * The indefinite plural name of the type.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getPluralLabel()
   */
  protected $label_plural = '';

  /**
   * A definite singular/plural name of the type.
   *
   * Needed keys: "singular" and "plural".
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getCountLabel()
   */
  protected $label_count = [];

  /**
   * A callable that can be used to provide the entity URI.
   *
   * @var callable|null
   */
  protected $uri_callback = NULL;

  /**
   * The machine name of the entity type group.
   *
   * @var string
   */
  protected $group;

  /**
   * The human-readable name of the entity type group.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getGroupLabel()
   */
  protected $group_label;

  /**
   * The route name used by field UI to attach its management pages.
   *
   * @var string
   */
  protected $field_ui_base_route;

  /**
   * Indicates whether this entity type is commonly used as a reference target.
   *
   * This is used by the Entity reference field to promote an entity type in the
   * add new field select list in Field UI.
   *
   * @var bool
   */
  protected $common_reference_target = FALSE;

  /**
   * The list cache contexts for this entity type.
   *
   * @var string[]
   */
  protected $list_cache_contexts = [];

  /**
   * The list cache tags for this entity type.
   *
   * @var string[]
   */
  protected $list_cache_tags = [];

  /**
   * Entity constraint definitions.
   *
   * @var array[]
   */
  protected $constraints = [];

  /**
   * Any additional properties and values.
   *
   * @var array
   */
  protected $additional = [];

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
    if (mb_strlen($definition['id']) > static::ID_MAX_LENGTH) {
      throw new EntityTypeIdLengthException('Attempt to create an entity type with an ID longer than ' . static::ID_MAX_LENGTH . " characters: {$definition['id']}.");
    }

    foreach ($definition as $property => $value) {
      $this->set($property, $value);
    }

    // Ensure defaults.
    $this->entity_keys += [
      'revision' => '',
      'bundle' => '',
      'langcode' => '',
      'default_langcode' => 'default_langcode',
      'revision_translation_affected' => 'revision_translation_affected',
    ];
    $this->handlers += [
      'access' => 'Drupal\Core\Entity\EntityAccessControlHandler',
    ];
    if (isset($this->handlers['storage'])) {
      $this->checkStorageClass($this->handlers['storage']);
    }

    // Automatically add the "EntityChanged" constraint if the entity type
    // tracks the changed time.
    if ($this->entityClassImplements(EntityChangedInterface::class)) {
      $this->addConstraint('EntityChanged');
    }
    // Automatically add the "EntityUntranslatableFields" constraint if we have
    // an entity type supporting translatable fields and pending revisions.
    if ($this->entityClassImplements(ContentEntityInterface::class)) {
      $this->addConstraint('EntityUntranslatableFields');
    }

    // Ensure a default list cache tag is set.
    if (empty($this->list_cache_tags)) {
      $this->list_cache_tags = [$definition['id'] . '_list'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($property) {
    if (property_exists($this, $property)) {
      $value = $this->{$property} ?? NULL;
    }
    else {
      $value = $this->additional[$property] ?? NULL;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->additional[$property] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return $this->internal;
  }

  /**
   * {@inheritdoc}
   */
  public function isStaticallyCacheable() {
    return $this->static_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderCacheable() {
    return $this->render_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function isPersistentlyCacheable() {
    return $this->persistent_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys() {
    return $this->entity_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey($key) {
    $keys = $this->getKeys();
    return $keys[$key] ?? FALSE;
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
  public function getOriginalClass() {
    return $this->originalClass ?: $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    if (!$this->originalClass && $this->class) {
      // If the original class is currently not set, set it to the current
      // class, assume that is the original class name.
      $this->originalClass = $this->class;
    }

    return parent::setClass($class);
  }

  /**
   * {@inheritdoc}
   */
  public function entityClassImplements($interface) {
    return is_subclass_of($this->getClass(), $interface);
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerClasses() {
    return $this->handlers;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerClass($handler_type, $nested = FALSE) {
    if ($this->hasHandlerClass($handler_type, $nested)) {
      $handlers = $this->getHandlerClasses();
      return $nested ? $handlers[$handler_type][$nested] : $handlers[$handler_type];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandlerClass($handler_type, $value) {
    $this->handlers[$handler_type] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandlerClass($handler_type, $nested = FALSE) {
    $handlers = $this->getHandlerClasses();
    if (!isset($handlers[$handler_type]) || ($nested && !isset($handlers[$handler_type][$nested]))) {
      return FALSE;
    }
    $handler = $handlers[$handler_type];
    if ($nested) {
      $handler = $handler[$nested];
    }
    return class_exists($handler);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return $this->getHandlerClass('storage');
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageClass($class) {
    $this->checkStorageClass($class);
    $this->handlers['storage'] = $class;
    return $this;
  }

  /**
   * Checks that the provided class is compatible with the current entity type.
   *
   * @param string $class
   *   The class to check.
   */
  protected function checkStorageClass($class) {
    // Nothing to check by default.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation) {
    return $this->getHandlerClass('form', $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function setFormClass($operation, $class) {
    $this->handlers['form'][$operation] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFormClasses() {
    return !empty($this->handlers['form']);
  }

  /**
   * {@inheritdoc}
   */
  public function hasRouteProviders() {
    return !empty($this->handlers['route_provider']);
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilderClass() {
    return $this->getHandlerClass('list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function setListBuilderClass($class) {
    $this->handlers['list_builder'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasListBuilderClass() {
    return $this->hasHandlerClass('list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilderClass() {
    return $this->getHandlerClass('view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function setViewBuilderClass($class) {
    $this->handlers['view_builder'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewBuilderClass() {
    return $this->hasHandlerClass('view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteProviderClasses() {
    return !empty($this->handlers['route_provider']) ? $this->handlers['route_provider'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlClass() {
    return $this->getHandlerClass('access');
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessClass($class) {
    $this->handlers['access'] = $class;
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
    return $this->permission_granularity;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTemplates() {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTemplate($key) {
    $links = $this->getLinkTemplates();
    return $links[$key] ?? FALSE;
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
  public function setLinkTemplate($key, $path) {
    if ($path[0] !== '/') {
      throw new \InvalidArgumentException('Link templates accepts paths, which have to start with a leading slash.');
    }

    $this->links[$key] = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleEntityType() {
    return $this->bundle_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleOf() {
    return $this->bundle_of;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    // If there is no bundle label defined, try to provide some sensible
    // fallbacks.
    if (!empty($this->bundle_label)) {
      return (string) $this->bundle_label;
    }
    elseif ($bundle_entity_type_id = $this->getBundleEntityType()) {
      return (string) \Drupal::entityTypeManager()->getDefinition($bundle_entity_type_id)->getLabel();
    }
    return (string) new TranslatableMarkup('@type_label bundle', ['@type_label' => $this->getLabel()], [], $this->getStringTranslation());
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return $this->base_table;
  }

  /**
   * {@inheritdoc}
   */
  public function showRevisionUi() {
    return $this->isRevisionable() && $this->show_revision_ui;
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
  public function getRevisionDataTable() {
    return $this->revision_data_table;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionTable() {
    return $this->revision_table;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTable() {
    return $this->data_table;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionLabel() {
    if (empty($this->label_collection)) {
      $label = $this->getLabel();
      $this->label_collection = new TranslatableMarkup('@label entities', ['@label' => $label], [], $this->getStringTranslation());
    }
    return $this->label_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getSingularLabel() {
    if (empty($this->label_singular)) {
      $lowercase_label = mb_strtolower($this->getLabel());
      $this->label_singular = $lowercase_label;
    }
    return $this->label_singular;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel() {
    if (empty($this->label_plural)) {
      $lowercase_label = $this->getSingularLabel();
      $this->label_plural = new TranslatableMarkup('@label entities', ['@label' => $lowercase_label], [], $this->getStringTranslation());
    }
    return $this->label_plural;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountLabel($count) {
    if (empty($this->label_count)) {
      return $this->formatPlural($count, '@count @label', '@count @label entities', ['@label' => $this->getSingularLabel()], ['context' => 'Entity type label']);
    }
    $options = [];
    if (isset($this->label_count['context'])) {
      $options['context'] = $this->label_count['context'];
    }
    return $this->formatPlural($count, $this->label_count['singular'], $this->label_count['plural'], [], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getUriCallback() {
    return $this->uri_callback;
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
    return !empty($this->group_label) ? $this->group_label : $this->t('Other', [], ['context' => 'Entity type group']);
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheContexts() {
    return $this->list_cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTags() {
    return $this->list_cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    // Return 'content' for the default implementation as important distinction
    // is that dependencies on other configuration entities are hard
    // dependencies and have to exist before creating the dependent entity.
    return 'content';
  }

  /**
   * {@inheritdoc}
   */
  public function isCommonReferenceTarget() {
    return $this->common_reference_target;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return $this->constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function setConstraints(array $constraints) {
    $this->constraints = $constraints;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addConstraint($constraint_name, $options = NULL) {
    $this->constraints[$constraint_name] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleConfigDependency($bundle) {
    // If this entity type uses entities to manage its bundles then depend on
    // the bundle entity.
    if ($bundle_entity_type_id = $this->getBundleEntityType()) {
      if (!$bundle_entity = \Drupal::entityTypeManager()->getStorage($bundle_entity_type_id)->load($bundle)) {
        throw new \LogicException(sprintf('Missing bundle entity, entity type %s, entity id %s.', $bundle_entity_type_id, $bundle));
      }
      $config_dependency = [
        'type' => 'config',
        'name' => $bundle_entity->getConfigDependencyName(),
      ];
    }
    else {
      // Depend on the provider of the entity type.
      $config_dependency = [
        'type' => 'module',
        'name' => $this->getProvider(),
      ];
    }

    return $config_dependency;
  }

}
