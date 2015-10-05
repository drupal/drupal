<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\Exception\AmbiguousEntityClassException;
use Drupal\Core\Entity\Exception\InvalidLinkTemplateException;
use Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionEvent;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages entity type plugin definitions.
 *
 * Each entity type definition array is set in the entity type's
 * annotation and altered by hook_entity_type_alter().
 *
 * The defaults for the plugin definition are provided in
 * \Drupal\Core\Entity\EntityManagerInterface::defaults.
 *
 * @see \Drupal\Core\Entity\Annotation\EntityType
 * @see \Drupal\Core\Entity\EntityInterface
 * @see \Drupal\Core\Entity\EntityTypeInterface
 * @see hook_entity_type_alter()
 */
class EntityManager extends DefaultPluginManager implements EntityManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;
  use StringTranslationTrait;

  /**
   * Extra fields by bundle.
   *
   * @var array
   */
  protected $extraFields = array();

  /**
   * Contains instantiated handlers keyed by handler type and entity type.
   *
   * @var array
   */
  protected $handlers = array();

  /**
   * Static cache of base field definitions.
   *
   * @var array
   */
  protected $baseFieldDefinitions;

  /**
   * Static cache of field definitions per bundle and entity type.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * Static cache of field storage definitions per entity type.
   *
   * Elements of the array:
   *  - $entity_type_id: \Drupal\Core\Field\BaseFieldDefinition[]
   *
   * @var array
   */
  protected $fieldStorageDefinitions;

  /**
   * The string translationManager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The keyvalue factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Static cache of bundle information.
   *
   * @var array
   */
  protected $bundleInfo;

  /**
   * Static cache of display modes information.
   *
   * @var array
   */
  protected $displayModeInfo = array();

  /**
   * An array keyed by entity type. Each value is an array whose keys are
   * field names and whose value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears.
   *
   * @return array
   */
  protected $fieldMap = array();

  /**
   * An array keyed by field type. Each value is an array whose key are entity
   * types including arrays in the same form that $fieldMap.
   *
   * It helps access the mapping between types and fields by the field type.
   *
   * @var array
   */
  protected $fieldMapByFieldType = array();

  /**
   * Contains cached mappings of class names to entity types.
   *
   * @var array
   */
  protected $classNameEntityTypeMap = array();

  /**
   * Constructs a new Entity plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translationManager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The keyvalue factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, TranslationInterface $translation_manager, ClassResolverInterface $class_resolver, TypedDataManagerInterface $typed_data_manager, KeyValueFactoryInterface $key_value_factory, EventDispatcherInterface $event_dispatcher) {
    parent::__construct('Entity', $namespaces, $module_handler, 'Drupal\Core\Entity\EntityInterface');

    $this->setCacheBackend($cache, 'entity_type', array('entity_types'));
    $this->alterInfo('entity_type');

    $this->discovery = new AnnotatedClassDiscovery('Entity', $namespaces, 'Drupal\Core\Entity\Annotation\EntityType');
    $this->languageManager = $language_manager;
    $this->translationManager = $translation_manager;
    $this->classResolver = $class_resolver;
    $this->typedDataManager = $typed_data_manager;
    $this->keyValueFactory = $key_value_factory;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->clearCachedBundles();
    $this->clearCachedFieldDefinitions();
    $this->classNameEntityTypeMap = array();
    $this->handlers = array();
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    parent::processDefinition($definition, $plugin_id);

    // All link templates must have a leading slash.
    foreach ((array) $definition->getLinkTemplates() as $link_relation_name => $link_template) {
      if ($link_template[0] != '/') {
        throw new InvalidLinkTemplateException("Link template '$link_relation_name' for entity type '$plugin_id' must start with a leading slash, the current link template is '$link_template'");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();

    // Directly call the hook implementations to pass the definitions to them
    // by reference, so new entity types can be added.
    foreach ($this->moduleHandler->getImplementations('entity_type_build') as $module) {
      $function = $module . '_' . 'entity_type_build';
      $function($definitions);
    }
    foreach ($definitions as $plugin_id => $definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    if (($entity_type = parent::getDefinition($entity_type_id, FALSE)) && class_exists($entity_type->getClass())) {
      return $entity_type;
    }
    elseif (!$exception_on_invalid) {
      return NULL;
    }

    throw new PluginNotFoundException($entity_type_id, sprintf('The "%s" entity type does not exist.', $entity_type_id));
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandler($entity_type, $handler_type) {
    if ($definition = $this->getDefinition($entity_type, FALSE)) {
      return $definition->hasHandlerClass($handler_type);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type) {
    return $this->getHandler($entity_type, 'storage');
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilder($entity_type) {
    return $this->getHandler($entity_type, 'list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_type, $operation) {
    if (!isset($this->handlers['form'][$operation][$entity_type])) {
      if (!$class = $this->getDefinition($entity_type, TRUE)->getFormClass($operation)) {
        throw new InvalidPluginDefinitionException($entity_type, sprintf('The "%s" entity type did not specify a "%s" form class.', $entity_type, $operation));
      }

      $form_object = $this->classResolver->getInstanceFromDefinition($class);

      $form_object
        ->setStringTranslation($this->translationManager)
        ->setModuleHandler($this->moduleHandler)
        ->setEntityManager($this)
        ->setOperation($operation);
      $this->handlers['form'][$operation][$entity_type] = $form_object;
    }
    return $this->handlers['form'][$operation][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteProviders($entity_type) {
    if (!isset($this->handlers['route_provider'][$entity_type])) {
      $route_provider_classes = $this->getDefinition($entity_type, TRUE)->getRouteProviderClasses();

      foreach ($route_provider_classes as $type => $class) {
        $this->handlers['route_provider'][$entity_type][$type] = $this->createHandlerInstance($class, $this->getDefinition($entity_type));
      }
    }
    return isset($this->handlers['route_provider'][$entity_type]) ? $this->handlers['route_provider'][$entity_type] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type) {
    return $this->getHandler($entity_type, 'view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlHandler($entity_type) {
    return $this->getHandler($entity_type, 'access');
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($entity_type, $handler_type) {
    if (!isset($this->handlers[$handler_type][$entity_type])) {
      $definition = $this->getDefinition($entity_type);
      $class = $definition->getHandlerClass($handler_type);
      if (!$class) {
        throw new InvalidPluginDefinitionException($entity_type, sprintf('The "%s" entity type did not specify a %s handler.', $entity_type, $handler_type));
      }
      $this->handlers[$handler_type][$entity_type] = $this->createHandlerInstance($class, $definition);
    }
    return $this->handlers[$handler_type][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = null) {
    if (is_subclass_of($class, 'Drupal\Core\Entity\EntityHandlerInterface')) {
      $handler = $class::createInstance($this->container, $definition);
    }
    else {
      $handler = new $class($definition);
    }
    if (method_exists($handler, 'setModuleHandler')) {
      $handler->setModuleHandler($this->moduleHandler);
    }
    if (method_exists($handler, 'setStringTranslation')) {
      $handler->setStringTranslation($this->translationManager);
    }
    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions($entity_type_id) {
    // Check the static cache.
    if (!isset($this->baseFieldDefinitions[$entity_type_id])) {
      // Not prepared, try to load from cache.
      $cid = 'entity_base_field_definitions:' . $entity_type_id . ':' . $this->languageManager->getCurrentLanguage()->getId();
      if ($cache = $this->cacheGet($cid)) {
        $this->baseFieldDefinitions[$entity_type_id] = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $this->baseFieldDefinitions[$entity_type_id] = $this->buildBaseFieldDefinitions($entity_type_id);
        $this->cacheSet($cid, $this->baseFieldDefinitions[$entity_type_id], Cache::PERMANENT, array('entity_types', 'entity_field_info'));
       }
     }
    return $this->baseFieldDefinitions[$entity_type_id];
  }

  /**
   * Builds base field definitions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\FieldableEntityInterface are supported.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name.
   *
   * @throws \LogicException
   *   Thrown if a config entity type is given or if one of the entity keys is
   *   flagged as translatable.
   */
  protected function buildBaseFieldDefinitions($entity_type_id) {
    $entity_type = $this->getDefinition($entity_type_id);
    $class = $entity_type->getClass();
    $keys = array_filter($entity_type->getKeys());

    // Fail with an exception for non-fieldable entity types.
    if (!$entity_type->isSubclassOf(FieldableEntityInterface::class)) {
      throw new \LogicException("Getting the base fields is not supported for entity type {$entity_type->getLabel()}.");
    }

    // Retrieve base field definitions.
    /** @var FieldStorageDefinitionInterface[] $base_field_definitions */
    $base_field_definitions = $class::baseFieldDefinitions($entity_type);

    // Make sure translatable entity types are correctly defined.
    if ($entity_type->isTranslatable()) {
      // The langcode field should always be translatable if the entity type is.
      if (isset($keys['langcode']) && isset($base_field_definitions[$keys['langcode']])) {
        $base_field_definitions[$keys['langcode']]->setTranslatable(TRUE);
      }
      // A default_langcode field should always be defined.
      if (!isset($base_field_definitions[$keys['default_langcode']])) {
        $base_field_definitions[$keys['default_langcode']] = BaseFieldDefinition::create('boolean')
          ->setLabel($this->t('Default translation'))
          ->setDescription($this->t('A flag indicating whether this is the default translation.'))
          ->setTranslatable(TRUE)
          ->setRevisionable(TRUE)
          ->setDefaultValue(TRUE);
      }
    }

    // Assign base field definitions the entity type provider.
    $provider = $entity_type->getProvider();
    foreach ($base_field_definitions as $definition) {
      // @todo Remove this check once FieldDefinitionInterface exposes a proper
      //  provider setter. See https://www.drupal.org/node/2225961.
      if ($definition instanceof BaseFieldDefinition) {
        $definition->setProvider($provider);
      }
    }

    // Retrieve base field definitions from modules.
    foreach ($this->moduleHandler->getImplementations('entity_base_field_info') as $module) {
      $module_definitions = $this->moduleHandler->invoke($module, 'entity_base_field_info', array($entity_type));
      if (!empty($module_definitions)) {
        // Ensure the provider key actually matches the name of the provider
        // defining the field.
        foreach ($module_definitions as $field_name => $definition) {
          // @todo Remove this check once FieldDefinitionInterface exposes a
          //  proper provider setter. See https://www.drupal.org/node/2225961.
          if ($definition instanceof BaseFieldDefinition && $definition->getProvider() == NULL) {
            $definition->setProvider($module);
          }
          $base_field_definitions[$field_name] = $definition;
        }
      }
    }

    // Automatically set the field name, target entity type and bundle
    // for non-configurable fields.
    foreach ($base_field_definitions as $field_name => $base_field_definition) {
      if ($base_field_definition instanceof BaseFieldDefinition) {
        $base_field_definition->setName($field_name);
        $base_field_definition->setTargetEntityTypeId($entity_type_id);
        $base_field_definition->setTargetBundle(NULL);
      }
    }

    // Invoke alter hook.
    $this->moduleHandler->alter('entity_base_field_info', $base_field_definitions, $entity_type);

    // Ensure defined entity keys are there and have proper revisionable and
    // translatable values.
    foreach (array_intersect_key($keys, array_flip(['id', 'revision', 'uuid', 'bundle'])) as $key => $field_name) {
      if (!isset($base_field_definitions[$field_name])) {
        throw new \LogicException("The $field_name field definition does not exist and it is used as $key entity key.");
      }
      if ($base_field_definitions[$field_name]->isRevisionable()) {
        throw new \LogicException("The {$base_field_definitions[$field_name]->getLabel()} field cannot be revisionable as it is used as $key entity key.");
      }
      if ($base_field_definitions[$field_name]->isTranslatable()) {
        throw new \LogicException("The {$base_field_definitions[$field_name]->getLabel()} field cannot be translatable as it is used as $key entity key.");
      }
    }

    // Make sure translatable entity types define the "langcode" field properly.
    if ($entity_type->isTranslatable() && (!isset($keys['langcode']) || !isset($base_field_definitions[$keys['langcode']]) || !$base_field_definitions[$keys['langcode']]->isTranslatable())) {
      throw new \LogicException("The {$entity_type->getLabel()} entity type cannot be translatable as it does not define a translatable \"langcode\" field.");
    }

    return $base_field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($entity_type_id, $bundle) {
    if (!isset($this->fieldDefinitions[$entity_type_id][$bundle])) {
      $base_field_definitions = $this->getBaseFieldDefinitions($entity_type_id);
      // Not prepared, try to load from cache.
      $cid = 'entity_bundle_field_definitions:' . $entity_type_id . ':' . $bundle . ':' . $this->languageManager->getCurrentLanguage()->getId();
      if ($cache = $this->cacheGet($cid)) {
        $bundle_field_definitions = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $bundle_field_definitions = $this->buildBundleFieldDefinitions($entity_type_id, $bundle, $base_field_definitions);
        $this->cacheSet($cid, $bundle_field_definitions, Cache::PERMANENT, array('entity_types', 'entity_field_info'));
      }
      // Field definitions consist of the bundle specific overrides and the
      // base fields, merge them together. Use array_replace() to replace base
      // fields with by bundle overrides and keep them in order, append
      // additional by bundle fields.
      $this->fieldDefinitions[$entity_type_id][$bundle] = array_replace($base_field_definitions, $bundle_field_definitions);
    }
    return $this->fieldDefinitions[$entity_type_id][$bundle];
  }

  /**
   * Builds field definitions for a specific bundle within an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\FieldableEntityInterface are supported.
   * @param string $bundle
   *   The bundle.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $base_field_definitions
   *   The list of base field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of bundle field definitions, keyed by field name. Does
   *   not include base fields.
   */
  protected function buildBundleFieldDefinitions($entity_type_id, $bundle, array $base_field_definitions) {
    $entity_type = $this->getDefinition($entity_type_id);
    $class = $entity_type->getClass();

    // Allow the entity class to provide bundle fields and bundle-specific
    // overrides of base fields.
    $bundle_field_definitions = $class::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    // Load base field overrides from configuration. These take precedence over
    // base field overrides returned above.
    $base_field_override_ids = array_map(function($field_name) use ($entity_type_id, $bundle) {
      return $entity_type_id . '.' . $bundle . '.' . $field_name;
    }, array_keys($base_field_definitions));
    $base_field_overrides = $this->getStorage('base_field_override')->loadMultiple($base_field_override_ids);
    foreach ($base_field_overrides as $base_field_override) {
      /** @var \Drupal\Core\Field\Entity\BaseFieldOverride $base_field_override */
      $field_name = $base_field_override->getName();
      $bundle_field_definitions[$field_name] = $base_field_override;
    }

    $provider = $entity_type->getProvider();
    foreach ($bundle_field_definitions as $definition) {
      // @todo Remove this check once FieldDefinitionInterface exposes a proper
      //  provider setter. See https://www.drupal.org/node/2225961.
      if ($definition instanceof BaseFieldDefinition) {
        $definition->setProvider($provider);
      }
    }

    // Retrieve base field definitions from modules.
    foreach ($this->moduleHandler->getImplementations('entity_bundle_field_info') as $module) {
      $module_definitions = $this->moduleHandler->invoke($module, 'entity_bundle_field_info', array($entity_type, $bundle, $base_field_definitions));
      if (!empty($module_definitions)) {
        // Ensure the provider key actually matches the name of the provider
        // defining the field.
        foreach ($module_definitions as $field_name => $definition) {
          // @todo Remove this check once FieldDefinitionInterface exposes a
          //  proper provider setter. See https://www.drupal.org/node/2225961.
          if ($definition instanceof BaseFieldDefinition) {
            $definition->setProvider($module);
          }
          $bundle_field_definitions[$field_name] = $definition;
        }
      }
    }

    // Automatically set the field name, target entity type and bundle
    // for non-configurable fields.
    foreach ($bundle_field_definitions as $field_name => $field_definition) {
      if ($field_definition instanceof BaseFieldDefinition) {
        $field_definition->setName($field_name);
        $field_definition->setTargetEntityTypeId($entity_type_id);
        $field_definition->setTargetBundle($bundle);
      }
    }

    // Invoke 'per bundle' alter hook.
    $this->moduleHandler->alter('entity_bundle_field_info', $bundle_field_definitions, $entity_type, $bundle);

    return $bundle_field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinitions($entity_type_id) {
    if (!isset($this->fieldStorageDefinitions[$entity_type_id])) {
      $this->fieldStorageDefinitions[$entity_type_id] = array();
      // Add all non-computed base fields.
      foreach ($this->getBaseFieldDefinitions($entity_type_id) as $field_name => $definition) {
        if (!$definition->isComputed()) {
          $this->fieldStorageDefinitions[$entity_type_id][$field_name] = $definition;
        }
      }
      // Not prepared, try to load from cache.
      $cid = 'entity_field_storage_definitions:' . $entity_type_id . ':' . $this->languageManager->getCurrentLanguage()->getId();
      if ($cache = $this->cacheGet($cid)) {
        $field_storage_definitions = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $field_storage_definitions = $this->buildFieldStorageDefinitions($entity_type_id);
        $this->cacheSet($cid, $field_storage_definitions, Cache::PERMANENT, array('entity_types', 'entity_field_info'));
      }
      $this->fieldStorageDefinitions[$entity_type_id] += $field_storage_definitions;
    }
    return $this->fieldStorageDefinitions[$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap() {
    if (!$this->fieldMap) {
      // Not prepared, try to load from cache.
      $cid = 'entity_field_map';
      if ($cache = $this->cacheGet($cid)) {
        $this->fieldMap = $cache->data;
      }
      else {
        // The field map is built in two steps. First, add all base fields, by
        // looping over all fieldable entity types. They always exist for all
        // bundles, and we do not expect to have so many different entity
        // types for this to become a bottleneck.
        foreach ($this->getDefinitions() as $entity_type_id => $entity_type) {
          if ($entity_type->isSubclassOf(FieldableEntityInterface::class)) {
            $bundles = array_keys($this->getBundleInfo($entity_type_id));
            foreach ($this->getBaseFieldDefinitions($entity_type_id) as $field_name => $base_field_definition) {
              $this->fieldMap[$entity_type_id][$field_name] = [
                'type' => $base_field_definition->getType(),
                'bundles' => array_combine($bundles, $bundles),
              ];
            }
          }
        }

        // In the second step, the per-bundle fields are added, based on the
        // persistent bundle field map stored in a key value collection. This
        // data is managed in the EntityManager::onFieldDefinitionCreate()
        // and EntityManager::onFieldDefinitionDelete() methods. Rebuilding this
        // information in the same way as base fields would not scale, as the
        // time to query would grow exponentially with more fields and bundles.
        // A cache would be deleted during cache clears, which is the only time
        // it is needed, so a key value collection is used.
        $bundle_field_maps = $this->keyValueFactory->get('entity.definitions.bundle_field_map')->getAll();
        foreach ($bundle_field_maps as $entity_type_id => $bundle_field_map) {
          foreach ($bundle_field_map as $field_name => $map_entry) {
            if (!isset($this->fieldMap[$entity_type_id][$field_name])) {
              $this->fieldMap[$entity_type_id][$field_name] = $map_entry;
            }
            else {
              $this->fieldMap[$entity_type_id][$field_name]['bundles'] += $map_entry['bundles'];
            }
          }
        }

        $this->cacheSet($cid, $this->fieldMap, Cache::PERMANENT, array('entity_types'));
      }
    }
    return $this->fieldMap;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapByFieldType($field_type) {
    if (!isset($this->fieldMapByFieldType[$field_type])) {
      $filtered_map = array();
      $map = $this->getFieldMap();
      foreach ($map as $entity_type => $fields) {
        foreach ($fields as $field_name => $field_info) {
          if ($field_info['type'] == $field_type) {
            $filtered_map[$entity_type][$field_name] = $field_info;
          }
        }
      }
      $this->fieldMapByFieldType[$field_type] = $filtered_map;
    }
    return $this->fieldMapByFieldType[$field_type];
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();

    // Notify the storage about the new field.
    $this->getStorage($entity_type_id)->onFieldDefinitionCreate($field_definition);

    // Update the bundle field map key value collection, add the new field.
    $bundle_field_map = $this->keyValueFactory->get('entity.definitions.bundle_field_map')->get($entity_type_id);
    if (!isset($bundle_field_map[$field_name])) {
      // This field did not exist yet, initialize it with the type and empty
      // bundle list.
      $bundle_field_map[$field_name] = [
        'type' => $field_definition->getType(),
        'bundles' => [],
      ];
    }
    $bundle_field_map[$field_name]['bundles'][$bundle] = $bundle;
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->set($entity_type_id, $bundle_field_map);

    // Delete the cache entry.
    $this->cacheBackend->delete('entity_field_map');

    // If the field map is initialized, update it as well, so that calls to it
    // do not have to rebuild it again.
    if ($this->fieldMap) {
      if (!isset($this->fieldMap[$entity_type_id][$field_name])) {
        // This field did not exist yet, initialize it with the type and empty
        // bundle list.
        $this->fieldMap[$entity_type_id][$field_name] = [
          'type' => $field_definition->getType(),
          'bundles' => [],
        ];
      }
      $this->fieldMap[$entity_type_id][$field_name]['bundles'][$bundle] = $bundle;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original) {
    // Notify the storage about the updated field.
    $this->getStorage($field_definition->getTargetEntityTypeId())->onFieldDefinitionUpdate($field_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();

    // Notify the storage about the field deletion.
    $this->getStorage($entity_type_id)->onFieldDefinitionDelete($field_definition);

    // Unset the bundle from the bundle field map key value collection.
    $bundle_field_map = $this->keyValueFactory->get('entity.definitions.bundle_field_map')->get($entity_type_id);
    unset($bundle_field_map[$field_name]['bundles'][$bundle]);
    if (empty($bundle_field_map[$field_name]['bundles'])) {
      // If there are no bundles left, remove the field from the map.
      unset($bundle_field_map[$field_name]);
    }
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->set($entity_type_id, $bundle_field_map);

    // Delete the cache entry.
    $this->cacheBackend->delete('entity_field_map');

    // If the field map is initialized, update it as well, so that calls to it
    // do not have to rebuild it again.
    if ($this->fieldMap) {
      unset($this->fieldMap[$entity_type_id][$field_name]['bundles'][$bundle]);
      if (empty($this->fieldMap[$entity_type_id][$field_name]['bundles'])) {
        unset($this->fieldMap[$entity_type_id][$field_name]);
      }
    }
  }

  /**
   * Builds field storage definitions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\FieldableEntityInterface are supported
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   An array of field storage definitions, keyed by field name.
   */
  protected function buildFieldStorageDefinitions($entity_type_id) {
    $entity_type = $this->getDefinition($entity_type_id);
    $field_definitions = array();

    // Retrieve base field definitions from modules.
    foreach ($this->moduleHandler->getImplementations('entity_field_storage_info') as $module) {
      $module_definitions = $this->moduleHandler->invoke($module, 'entity_field_storage_info', array($entity_type));
      if (!empty($module_definitions)) {
        // Ensure the provider key actually matches the name of the provider
        // defining the field.
        foreach ($module_definitions as $field_name => $definition) {
          // @todo Remove this check once FieldDefinitionInterface exposes a
          //  proper provider setter. See https://www.drupal.org/node/2225961.
          if ($definition instanceof BaseFieldDefinition) {
            $definition->setProvider($module);
          }
          $field_definitions[$field_name] = $definition;
        }
      }
    }

    // Invoke alter hook.
    $this->moduleHandler->alter('entity_field_storage_info', $field_definitions, $entity_type);

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedFieldDefinitions() {
    $this->baseFieldDefinitions = array();
    $this->fieldDefinitions = array();
    $this->fieldStorageDefinitions = array();
    $this->fieldMap = array();
    $this->fieldMapByFieldType = array();
    $this->displayModeInfo = array();
    $this->extraFields = array();
    Cache::invalidateTags(array('entity_field_info'));
    // The typed data manager statically caches prototype objects with injected
    // definitions, clear those as well.
    $this->typedDataManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedBundles() {
    $this->bundleInfo = array();
    Cache::invalidateTags(array('entity_bundles'));
    // Entity bundles are exposed as data types, clear that cache too.
    $this->typedDataManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleInfo($entity_type) {
    $bundle_info = $this->getAllBundleInfo();
    return isset($bundle_info[$entity_type]) ? $bundle_info[$entity_type] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBundleInfo() {
    if (empty($this->bundleInfo)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      if ($cache = $this->cacheGet("entity_bundle_info:$langcode")) {
        $this->bundleInfo = $cache->data;
      }
      else {
        $this->bundleInfo = $this->moduleHandler->invokeAll('entity_bundle_info');
        // First look for entity types that act as bundles for others, load them
        // and add them as bundles.
        foreach ($this->getDefinitions() as $type => $entity_type) {
          if ($entity_type->getBundleOf()) {
            foreach ($this->getStorage($type)->loadMultiple() as $entity) {
              $this->bundleInfo[$entity_type->getBundleOf()][$entity->id()]['label'] = $entity->label();
            }
          }
        }
        foreach ($this->getDefinitions() as $type => $entity_type) {
          // If no bundles are provided, use the entity type name and label.
          if (!isset($this->bundleInfo[$type])) {
            $this->bundleInfo[$type][$type]['label'] = $entity_type->getLabel();
          }
        }
        $this->moduleHandler->alter('entity_bundle_info', $this->bundleInfo);
        $this->cacheSet("entity_bundle_info:$langcode", $this->bundleInfo, Cache::PERMANENT, array('entity_types', 'entity_bundles'));
      }
    }

    return $this->bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFields($entity_type_id, $bundle) {
    // Read from the "static" cache.
    if (isset($this->extraFields[$entity_type_id][$bundle])) {
      return $this->extraFields[$entity_type_id][$bundle];
    }

    // Read from the persistent cache. Since hook_entity_extra_field_info() and
    // hook_entity_extra_field_info_alter() might contain t() calls, we cache
    // per language.
    $cache_id = 'entity_bundle_extra_fields:' . $entity_type_id . ':' . $bundle . ':' . $this->languageManager->getCurrentLanguage()->getId();
    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      $this->extraFields[$entity_type_id][$bundle] = $cached->data;
      return $this->extraFields[$entity_type_id][$bundle];
    }

    $extra = $this->moduleHandler->invokeAll('entity_extra_field_info');
    $this->moduleHandler->alter('entity_extra_field_info', $extra);
    $info = isset($extra[$entity_type_id][$bundle]) ? $extra[$entity_type_id][$bundle] : array();
    $info += array(
      'form' => array(),
      'display' => array(),
    );

    // Store in the 'static' and persistent caches.
    $this->extraFields[$entity_type_id][$bundle] = $info;
    $this->cacheSet($cache_id, $info, Cache::PERMANENT, array(
      'entity_field_info',
    ));

    return $this->extraFields[$entity_type_id][$bundle];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels($group = FALSE) {
    $options = array();
    $definitions = $this->getDefinitions();

    foreach ($definitions as $entity_type_id => $definition) {
      if ($group) {
        $options[(string) $definition->getGroupLabel()][$entity_type_id] = $definition->getLabel();
      }
      else {
        $options[$entity_type_id] = $definition->getLabel();
      }
    }

    if ($group) {
      foreach ($options as &$group_options) {
        // Sort the list alphabetically by group label.
        array_multisort($group_options, SORT_ASC, SORT_NATURAL);
      }

      // Make sure that the 'Content' group is situated at the top.
      $content = $this->t('Content', array(), array('context' => 'Entity type group'));
      $options = array((string) $content => $options[(string) $content]) + $options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = array()) {
    $translation = $entity;

    if ($entity instanceof TranslatableInterface && count($entity->getTranslationLanguages()) > 1) {
      if (empty($langcode)) {
        $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $entity->addCacheContexts(['languages:' . LanguageInterface::TYPE_CONTENT]);
      }

      // Retrieve language fallback candidates to perform the entity language
      // negotiation, unless the current translation is already the desired one.
      if ($entity->language()->getId() != $langcode) {
        $context['data'] = $entity;
        $context += array('operation' => 'entity_view', 'langcode' => $langcode);
        $candidates = $this->languageManager->getFallbackCandidates($context);

        // Ensure the default language has the proper language code.
        $default_language = $entity->getUntranslated()->language();
        $candidates[$default_language->getId()] = LanguageInterface::LANGCODE_DEFAULT;

        // Return the most fitting entity translation.
        foreach ($candidates as $candidate) {
          if ($entity->hasTranslation($candidate)) {
            $translation = $entity->getTranslation($candidate);
            break;
          }
        }
      }
    }

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllViewModes() {
    return $this->getAllDisplayModesByEntityType('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes($entity_type_id) {
    return $this->getDisplayModesByEntityType('view_mode', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFormModes() {
    return $this->getAllDisplayModesByEntityType('form_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModes($entity_type_id) {
    return $this->getDisplayModesByEntityType('form_mode', $entity_type_id);
  }

  /**
   * Gets the entity display mode info for all entity types.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   *
   * @return array
   *   The display mode info for all entity types.
   */
  protected function getAllDisplayModesByEntityType($display_type) {
    if (!isset($this->displayModeInfo[$display_type])) {
      $key = 'entity_' . $display_type . '_info';
      $entity_type_id = 'entity_' . $display_type;
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();
      if ($cache = $this->cacheGet("$key:$langcode")) {
        $this->displayModeInfo[$display_type] = $cache->data;
      }
      else {
        $this->displayModeInfo[$display_type] = array();
        foreach ($this->getStorage($entity_type_id)->loadMultiple() as $display_mode) {
          list($display_mode_entity_type, $display_mode_name) = explode('.', $display_mode->id(), 2);
          $this->displayModeInfo[$display_type][$display_mode_entity_type][$display_mode_name] = $display_mode->toArray();
        }
        $this->moduleHandler->alter($key, $this->displayModeInfo[$display_type]);
        $this->cacheSet("$key:$langcode", $this->displayModeInfo[$display_type], CacheBackendInterface::CACHE_PERMANENT, array('entity_types', 'entity_field_info'));
      }
    }

    return $this->displayModeInfo[$display_type];
  }

  /**
   * Gets the entity display mode info for a specific entity type.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode info should be returned.
   *
   * @return array
   *   The display mode info for a specific entity type.
   */
  protected function getDisplayModesByEntityType($display_type, $entity_type_id) {
    if (isset($this->displayModeInfo[$display_type][$entity_type_id])) {
      return $this->displayModeInfo[$display_type][$entity_type_id];
    }
    else {
      $display_modes = $this->getAllDisplayModesByEntityType($display_type);
      if (isset($display_modes[$entity_type_id])) {
        return $display_modes[$entity_type_id];
      }
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptions($entity_type_id) {
    return $this->getDisplayModeOptions('view_mode', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptions($entity_type_id) {
    return $this->getDisplayModeOptions('form_mode', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->getDisplayModeOptionsByBundle('view_mode', $entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->getDisplayModeOptionsByBundle('form_mode', $entity_type_id, $bundle);
  }

  /**
   * Gets an array of display mode options.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode options should be returned.
   *
   * @return array
   *   An array of display mode labels, keyed by the display mode ID.
   */
  protected function getDisplayModeOptions($display_type, $entity_type_id) {
    $options = array('default' => t('Default'));
    foreach ($this->getDisplayModesByEntityType($display_type, $entity_type_id) as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }
    return $options;
  }

  /**
   * Returns an array of display mode options by bundle.
   *
   * @param $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of display mode labels, keyed by the display mode ID.
   */
  protected function getDisplayModeOptionsByBundle($display_type, $entity_type_id, $bundle) {
    // Collect all the entity's display modes.
    $options = $this->getDisplayModeOptions($display_type, $entity_type_id);

    // Filter out modes for which the entity display is disabled
    // (or non-existent).
    $load_ids = array();
    // Get the list of available entity displays for the current bundle.
    foreach (array_keys($options) as $mode) {
      $load_ids[] = $entity_type_id . '.' . $bundle . '.' . $mode;
    }

    // Load the corresponding displays.
    $displays = $this->getStorage($display_type == 'form_mode' ? 'entity_form_display' : 'entity_view_display')
      ->loadMultiple($load_ids);

    // Unset the display modes that are not active or do not exist.
    foreach (array_keys($options) as $mode) {
      $display_id = $entity_type_id . '.' . $bundle . '.' . $mode;
      if (!isset($displays[$display_id]) || !$displays[$display_id]->status()) {
        unset($options[$mode]);
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByUuid($entity_type_id, $uuid) {
    $entity_type = $this->getDefinition($entity_type_id);

    if (!$uuid_key = $entity_type->getKey('uuid')) {
      throw new EntityStorageException("Entity type $entity_type_id does not support UUIDs.");
    }

    $entities = $this->getStorage($entity_type_id)->loadByProperties(array($uuid_key => $uuid));

    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByConfigTarget($entity_type_id, $target) {
    $entity_type = $this->getDefinition($entity_type_id);

    // For configuration entities, the config target is given by the entity ID.
    // @todo Consider adding a method to allow entity types to indicate the
    //   target identifier key rather than hard-coding this check. Issue:
    //   https://www.drupal.org/node/2412983.
    if ($entity_type instanceof ConfigEntityType) {
      $entity = $this->getStorage($entity_type_id)->load($target);
    }

    // For content entities, the config target is given by the UUID.
    else {
      $entity = $this->loadEntityByUuid($entity_type_id, $target);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeFromClass($class_name) {

    // Check the already calculated classes first.
    if (isset($this->classNameEntityTypeMap[$class_name])) {
      return $this->classNameEntityTypeMap[$class_name];
    }

    $same_class = 0;
    $entity_type_id = NULL;
    foreach ($this->getDefinitions() as $entity_type) {
      if ($entity_type->getOriginalClass() == $class_name  || $entity_type->getClass() == $class_name) {
        $entity_type_id = $entity_type->id();
        if ($same_class++) {
          throw new AmbiguousEntityClassException($class_name);
        }
      }
    }

    // Return the matching entity type ID if there is one.
    if ($entity_type_id) {
      $this->classNameEntityTypeMap[$class_name] = $entity_type_id;
      return $entity_type_id;
    }

    throw new NoCorrespondingEntityClassException($class_name);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onEntityTypeCreate($entity_type);
    }

    $this->eventDispatcher->dispatch(EntityTypeEvents::CREATE, new EntityTypeEvent($entity_type));

    $this->setLastInstalledDefinition($entity_type);
    if ($entity_type->isSubclassOf(FieldableEntityInterface::class)) {
      $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $this->getFieldStorageDefinitions($entity_type_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onEntityTypeUpdate($entity_type, $original);
    }

    $this->eventDispatcher->dispatch(EntityTypeEvents::UPDATE, new EntityTypeEvent($entity_type, $original));

    $this->setLastInstalledDefinition($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onEntityTypeDelete($entity_type);
    }

    $this->eventDispatcher->dispatch(EntityTypeEvents::DELETE, new EntityTypeEvent($entity_type));

    $this->deleteLastInstalledDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof FieldStorageDefinitionListenerInterface) {
      $storage->onFieldStorageDefinitionCreate($storage_definition);
    }

    $this->eventDispatcher->dispatch(FieldStorageDefinitionEvents::CREATE, new FieldStorageDefinitionEvent($storage_definition));

    $this->setLastInstalledFieldStorageDefinition($storage_definition);
    $this->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof FieldStorageDefinitionListenerInterface) {
      $storage->onFieldStorageDefinitionUpdate($storage_definition, $original);
    }

    $this->eventDispatcher->dispatch(FieldStorageDefinitionEvents::UPDATE, new FieldStorageDefinitionEvent($storage_definition, $original));

    $this->setLastInstalledFieldStorageDefinition($storage_definition);
    $this->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof FieldStorageDefinitionListenerInterface) {
      $storage->onFieldStorageDefinitionDelete($storage_definition);
    }

    $this->eventDispatcher->dispatch(FieldStorageDefinitionEvents::DELETE, new FieldStorageDefinitionEvent($storage_definition));

    $this->deleteLastInstalledFieldStorageDefinition($storage_definition);
    $this->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleCreate($bundle, $entity_type_id) {
    $this->clearCachedBundles();
    // Notify the entity storage.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof EntityBundleListenerInterface) {
      $storage->onBundleCreate($bundle, $entity_type_id);
    }
    // Invoke hook_entity_bundle_create() hook.
    $this->moduleHandler->invokeAll('entity_bundle_create', array($entity_type_id, $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle, $entity_type_id) {
    $this->clearCachedBundles();
    // Notify the entity storage.
    $storage = $this->getStorage($entity_type_id);
    if ($storage instanceof EntityBundleListenerInterface) {
      $storage->onBundleDelete($bundle, $entity_type_id);
    }
    // Invoke hook_entity_bundle_delete() hook.
    $this->moduleHandler->invokeAll('entity_bundle_delete', array($entity_type_id, $bundle));
    $this->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledDefinition($entity_type_id) {
    return $this->keyValueFactory->get('entity.definitions.installed')->get($entity_type_id . '.entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    parent::useCaches($use_caches);
    if (!$use_caches) {
      $this->handlers = [];
      $this->fieldDefinitions = [];
      $this->baseFieldDefinitions = [];
      $this->fieldStorageDefinitions = [];
    }
  }

  /**
   * Stores the entity type definition in the application state.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  protected function setLastInstalledDefinition(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $this->keyValueFactory->get('entity.definitions.installed')->set($entity_type_id . '.entity_type', $entity_type);
  }

  /**
   * Deletes the entity type definition from the application state.
   *
   * @param string $entity_type_id
   *   The entity type definition identifier.
   */
  protected function deleteLastInstalledDefinition($entity_type_id) {
    $this->keyValueFactory->get('entity.definitions.installed')->delete($entity_type_id . '.entity_type');
    // Clean up field storage definitions as well. Even if the entity type
    // isn't currently fieldable, there might be legacy definitions or an
    // empty array stored from when it was.
    $this->keyValueFactory->get('entity.definitions.installed')->delete($entity_type_id . '.field_storage_definitions');
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id) {
    return $this->keyValueFactory->get('entity.definitions.installed')->get($entity_type_id . '.field_storage_definitions', array());
  }

  /**
   * Stores the entity type's field storage definitions in the application state.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   An array of field storage definitions.
   */
  protected function setLastInstalledFieldStorageDefinitions($entity_type_id, array $storage_definitions) {
    $this->keyValueFactory->get('entity.definitions.installed')->set($entity_type_id . '.field_storage_definitions', $storage_definitions);
  }

  /**
   * Stores the field storage definition in the application state.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  protected function setLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();
    $definitions = $this->getLastInstalledFieldStorageDefinitions($entity_type_id);
    $definitions[$storage_definition->getName()] = $storage_definition;
    $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
  }

  /**
   * Deletes the field storage definition from the application state.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  protected function deleteLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();
    $definitions = $this->getLastInstalledFieldStorageDefinitions($entity_type_id);
    unset($definitions[$storage_definition->getName()]);
    $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
  }

}
