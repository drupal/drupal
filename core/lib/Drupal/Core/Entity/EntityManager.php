<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
   * Contains instantiated controllers keyed by controller type and entity type.
   *
   * @var array
   */
  protected $controllers = array();

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
   *  - $entity_type_id: \Drupal\Core\Field\FieldDefinition[]
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
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, TranslationInterface $translation_manager, ClassResolverInterface $class_resolver, TypedDataManager $typed_data_manager) {
    parent::__construct('Entity', $namespaces, $module_handler, 'Drupal\Core\Entity\Annotation\EntityType');

    $this->setCacheBackend($cache, 'entity_type', array('entity_types' => TRUE));
    $this->alterInfo('entity_type');

    $this->languageManager = $language_manager;
    $this->translationManager = $translation_manager;
    $this->classResolver = $class_resolver;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->clearCachedBundles();
    $this->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->discovery->getDefinitions();

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
  public function hasController($entity_type, $controller_type) {
    if ($definition = $this->getDefinition($entity_type, FALSE)) {
      return $definition->hasControllerClass($controller_type);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type) {
    return $this->getController($entity_type, 'storage', 'getStorageClass');
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilder($entity_type) {
    return $this->getController($entity_type, 'list_builder', 'getListBuilderClass');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_type, $operation) {
    if (!isset($this->controllers['form'][$operation][$entity_type])) {
      if (!$class = $this->getDefinition($entity_type, TRUE)->getFormClass($operation)) {
        throw new InvalidPluginDefinitionException($entity_type, sprintf('The "%s" entity type did not specify a "%s" form class.', $entity_type, $operation));
      }

      $controller = $this->classResolver->getInstanceFromDefinition($class);

      $controller
        ->setStringTranslation($this->translationManager)
        ->setModuleHandler($this->moduleHandler)
        ->setOperation($operation);
      $this->controllers['form'][$operation][$entity_type] = $controller;
    }
    return $this->controllers['form'][$operation][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type) {
    return $this->getController($entity_type, 'view_builder', 'getViewBuilderClass');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessController($entity_type) {
    return $this->getController($entity_type, 'access', 'getAccessClass');
  }

  /**
   * Creates a new controller instance.
   *
   * @param string $entity_type
   *   The entity type for this controller.
   * @param string $controller_type
   *   The controller type to create an instance for.
   * @param string $controller_class_getter
   *   (optional) The method to call on the entity type object to get the controller class.
   *
   * @return mixed
   *   A controller instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getController($entity_type, $controller_type, $controller_class_getter = NULL) {
    if (!isset($this->controllers[$controller_type][$entity_type])) {
      $definition = $this->getDefinition($entity_type);
      if ($controller_class_getter) {
        $class = $definition->{$controller_class_getter}();
      }
      else {
        $class = $definition->getControllerClass($controller_type);
      }
      if (!$class) {
        throw new InvalidPluginDefinitionException($entity_type, sprintf('The "%s" entity type did not specify a %s class.', $entity_type, $controller_type));
      }
      if (is_subclass_of($class, 'Drupal\Core\Entity\EntityControllerInterface')) {
        $controller = $class::createInstance($this->container, $definition);
      }
      else {
        $controller = new $class($definition);
      }
      if (method_exists($controller, 'setModuleHandler')) {
        $controller->setModuleHandler($this->moduleHandler);
      }
      if (method_exists($controller, 'setStringTranslation')) {
        $controller->setStringTranslation($this->translationManager);
      }
      $this->controllers[$controller_type][$entity_type] = $controller;
    }
    return $this->controllers[$controller_type][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminRouteInfo($entity_type_id, $bundle) {
    if (($entity_type = $this->getDefinition($entity_type_id, FALSE)) && $admin_form = $entity_type->getLinkTemplate('admin-form')) {
      return array(
        'route_name' => $admin_form,
        'route_parameters' => array(
          $entity_type->getBundleEntityType() => $bundle,
        ),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions($entity_type_id) {
    // Check the static cache.
    if (!isset($this->baseFieldDefinitions[$entity_type_id])) {
      // Not prepared, try to load from cache.
      $cid = 'entity_base_field_definitions:' . $entity_type_id . ':' . $this->languageManager->getCurrentLanguage()->id;
      if ($cache = $this->cacheBackend->get($cid)) {
        $this->baseFieldDefinitions[$entity_type_id] = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $this->baseFieldDefinitions[$entity_type_id] = $this->buildBaseFieldDefinitions($entity_type_id);
        $this->cacheBackend->set($cid, $this->baseFieldDefinitions[$entity_type_id], Cache::PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
       }
     }
    return $this->baseFieldDefinitions[$entity_type_id];
  }

  /**
   * Builds base field definitions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\ContentEntityInterface are supported.
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

    // Fail with an exception for config entity types.
    if (!$entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      throw new \LogicException(String::format('Getting the base fields is not supported for entity type @type.', array('@type' => $entity_type->getLabel())));
    }

    // Retrieve base field definitions and assign them the entity type provider.
    $base_field_definitions = $class::baseFieldDefinitions($entity_type);
    $provider = $entity_type->getProvider();
    foreach ($base_field_definitions as $definition) {
      // @todo Remove this check once FieldDefinitionInterface exposes a proper
      //  provider setter. See https://drupal.org/node/2225961.
      if ($definition instanceof FieldDefinition) {
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
          //  proper provider setter. See https://drupal.org/node/2225961.
          if ($definition instanceof FieldDefinition && $definition->getProvider() == NULL) {
            $definition->setProvider($module);
          }
          $base_field_definitions[$field_name] = $definition;
        }
      }
    }

    // Automatically set the field name, target entity type and bundle
    // for non-configurable fields.
    foreach ($base_field_definitions as $field_name => $base_field_definition) {
      if ($base_field_definition instanceof FieldDefinition) {
        $base_field_definition->setName($field_name);
        $base_field_definition->setTargetEntityTypeId($entity_type_id);
        $base_field_definition->setBundle(NULL);
      }
    }

    // Invoke alter hook.
    $this->moduleHandler->alter('entity_base_field_info', $base_field_definitions, $entity_type);

    // Ensure defined entity keys are there and have proper revisionable and
    // translatable values.
    $keys = array_filter($entity_type->getKeys() + array('langcode' => 'langcode'));
    foreach ($keys as $key => $field_name) {
      if (isset($base_field_definitions[$field_name]) && in_array($key, array('id', 'revision', 'uuid', 'bundle')) && $base_field_definitions[$field_name]->isRevisionable()) {
        throw new \LogicException(String::format('The @field field cannot be revisionable as it is used as @key entity key.', array('@field' => $base_field_definitions[$field_name]->getLabel(), '@key' => $key)));
      }
      if (isset($base_field_definitions[$field_name]) && in_array($key, array('id', 'revision', 'uuid', 'bundle', 'langcode')) && $base_field_definitions[$field_name]->isTranslatable()) {
        throw new \LogicException(String::format('The @field field cannot be translatable as it is used as @key entity key.', array('@field' => $base_field_definitions[$field_name]->getLabel(), '@key' => $key)));
      }
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
      $cid = 'entity_bundle_field_definitions:' . $entity_type_id . ':' . $bundle . ':' . $this->languageManager->getCurrentLanguage()->id;
      if ($cache = $this->cacheBackend->get($cid)) {
        $bundle_field_definitions = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $bundle_field_definitions = $this->buildBundleFieldDefinitions($entity_type_id, $bundle, $base_field_definitions);
        $this->cacheBackend->set($cid, $bundle_field_definitions, Cache::PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
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
   *   \Drupal\Core\Entity\ContentEntityInterface are supported.
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

    // Allow the entity class to override the base fields.
    $bundle_field_definitions = $class::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);
    $provider = $entity_type->getProvider();
    foreach ($bundle_field_definitions as $definition) {
      // @todo Remove this check once FieldDefinitionInterface exposes a proper
      //  provider setter. See https://drupal.org/node/2225961.
      if ($definition instanceof FieldDefinition) {
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
          //  proper provider setter. See https://drupal.org/node/2225961.
          if ($definition instanceof FieldDefinition) {
            $definition->setProvider($module);
          }
          $bundle_field_definitions[$field_name] = $definition;
        }
      }
    }

    // Automatically set the field name, target entity type and bundle
    // for non-configurable fields.
    foreach ($bundle_field_definitions as $field_name => $field_definition) {
      if ($field_definition instanceof FieldDefinition) {
        $field_definition->setName($field_name);
        $field_definition->setTargetEntityTypeId($entity_type_id);
        $field_definition->setBundle($bundle);
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
      $cid = 'entity_field_storage_definitions:' . $entity_type_id . ':' . $this->languageManager->getCurrentLanguage()->id;
      if ($cache = $this->cacheBackend->get($cid)) {
        $field_storage_definitions = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $field_storage_definitions = $this->buildFieldStorageDefinitions($entity_type_id);
        $this->cacheBackend->set($cid, $field_storage_definitions, Cache::PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
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
      if ($cache = $this->cacheBackend->get($cid)) {
        $this->fieldMap = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        foreach ($this->getDefinitions() as $entity_type_id => $entity_type) {
          if ($entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
            foreach ($this->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
              foreach ($this->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
                $this->fieldMap[$entity_type_id][$field_name]['type'] = $field_definition->getType();
                $this->fieldMap[$entity_type_id][$field_name]['bundles'][] = $bundle;
              }
            }
          }
        }

        $this->cacheBackend->set($cid, $this->fieldMap, Cache::PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
      }
    }
    return $this->fieldMap;
  }

  /**
   * Builds field storage definitions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\ContentEntityInterface are supported
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
          //  proper provider setter. See https://drupal.org/node/2225961.
          if ($definition instanceof FieldDefinition) {
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
    $this->displayModeInfo = array();
    $this->extraFields = array();
    Cache::deleteTags(array('entity_field_info' => TRUE));
    // The typed data manager statically caches prototype objects with injected
    // definitions, clear those as well.
    $this->typedDataManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedBundles() {
    $this->bundleInfo = array();
    Cache::deleteTags(array('entity_bundles' => TRUE));
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
      $langcode = $this->languageManager->getCurrentLanguage()->id;
      if ($cache = $this->cacheBackend->get("entity_bundle_info:$langcode")) {
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
        $this->cacheBackend->set("entity_bundle_info:$langcode", $this->bundleInfo, Cache::PERMANENT, array('entity_types' => TRUE, 'entity_bundles' => TRUE));
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
    $cache_id = 'entity_bundle_extra_fields:' . $entity_type_id . ':' . $bundle . ':' . $this->languageManager->getCurrentLanguage()->id;
    $cached = $this->cacheBackend->get($cache_id);
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
    $this->cacheBackend->set($cache_id, $info, Cache::PERMANENT, array(
      'entity_field_info' => TRUE,
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
        $options[$definition->getGroupLabel()][$entity_type_id] = $definition->getLabel();
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
      $options = array($content => $options[$content]) + $options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = array()) {
    $translation = $entity;

    if ($entity instanceof TranslatableInterface) {
      if (empty($langcode)) {
        $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->id;
      }

      // Retrieve language fallback candidates to perform the entity language
      // negotiation.
      $context['data'] = $entity;
      $context += array('operation' => 'entity_view');
      $candidates = $this->languageManager->getFallbackCandidates($langcode, $context);

      // Ensure the default language has the proper language code.
      $default_language = $entity->getUntranslated()->language();
      $candidates[$default_language->id] = LanguageInterface::LANGCODE_DEFAULT;

      // Return the most fitting entity translation.
      foreach ($candidates as $candidate) {
        if ($entity->hasTranslation($candidate)) {
          $translation = $entity->getTranslation($candidate);
          break;
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
   * Returns the entity display mode info for all entity types.
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
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->id;
      if ($cache = $this->cacheBackend->get("$key:$langcode")) {
        $this->displayModeInfo[$display_type] = $cache->data;
      }
      else {
        $this->displayModeInfo[$display_type] = array();
        foreach ($this->getStorage($display_type)->loadMultiple() as $display_mode) {
          list($display_mode_entity_type, $display_mode_name) = explode('.', $display_mode->id(), 2);
          $this->displayModeInfo[$display_type][$display_mode_entity_type][$display_mode_name] = $display_mode->toArray();
        }
        $this->moduleHandler->alter($key, $this->displayModeInfo[$display_type]);
        $this->cacheBackend->set("$key:$langcode", $this->displayModeInfo[$display_type], CacheBackendInterface::CACHE_PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
      }
    }

    return $this->displayModeInfo[$display_type];
  }

  /**
   * Returns the entity display mode info for a specific entity type.
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
  public function getViewModeOptions($entity_type, $include_disabled = FALSE) {
    return $this->getDisplayModeOptions('view_mode', $entity_type, $include_disabled);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptions($entity_type, $include_disabled = FALSE) {
    return $this->getDisplayModeOptions('form_mode', $entity_type, $include_disabled);
  }

  /**
   * Returns an array of display mode options.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode options should be returned.
   * @param bool $include_disabled
   *   Force to include disabled display modes. Defaults to FALSE.
   *
   * @return array
   *   An array of display mode labels, keyed by the display mode ID.
   */
  protected function getDisplayModeOptions($display_type, $entity_type_id, $include_disabled = FALSE) {
    $options = array('default' => t('Default'));
    foreach ($this->getDisplayModesByEntityType($display_type, $entity_type_id) as $mode => $settings) {
      if (!empty($settings['status']) || $include_disabled) {
        $options[$mode] = $settings['label'];
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

}
