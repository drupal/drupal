<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class EntityManager extends PluginManagerBase implements EntityManagerInterface {

  /**
   * The injection container that should be passed into the controller factory.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Contains instantiated controllers keyed by controller type and entity type.
   *
   * @var array
   */
  protected $controllers = array();

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

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
   * The root paths.
   *
   * @see self::__construct().
   *
   * @var \Traversable
   */
  protected $namespaces;

  /**
   * The string translationManager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Static cache of bundle information.
   *
   * @var array
   */
  protected $bundleInfo;

  /**
   * Constructs a new Entity plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translationManager.
   */
  public function __construct(\Traversable $namespaces, ContainerInterface $container, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, LanguageManager $language_manager, TranslationInterface $translation_manager) {
    // Allow the plugin definition to be altered by hook_entity_type_alter().

    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
    $this->languageManager = $language_manager;
    $this->namespaces = $namespaces;
    $this->translationManager = $translation_manager;

    $this->discovery = new AnnotatedClassDiscovery('Entity', $namespaces, 'Drupal\Core\Entity\Annotation\EntityType');
    $this->discovery = new InfoHookDecorator($this->discovery, 'entity_type_build');
    $this->discovery = new AlterDecorator($this->discovery, 'entity_type');
    $this->discovery = new CacheDecorator($this->discovery, 'entity_type:' . $this->languageManager->getCurrentLanguage()->id, 'cache', Cache::PERMANENT, array('entity_types' => TRUE));

    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();

    $this->bundleInfo = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = FALSE) {
    if (($entity_type = parent::getDefinition($entity_type_id)) && class_exists($entity_type->getClass())) {
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
    if ($definition = $this->getDefinition($entity_type)) {
      return $definition->hasControllerClass($controller_type);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageController($entity_type) {
    return $this->getController($entity_type, 'storage', 'getStorageClass');
  }

  /**
   * {@inheritdoc}
   */
  public function getListController($entity_type) {
    return $this->getController($entity_type, 'list', 'getListClass');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormController($entity_type, $operation) {
    if (!isset($this->controllers['form'][$operation][$entity_type])) {
      if (!$class = $this->getDefinition($entity_type, TRUE)->getFormClass($operation)) {
        throw new InvalidPluginDefinitionException($entity_type, sprintf('The "%s" entity type did not specify a "%s" form class.', $entity_type, $operation));
      }
      if (in_array('Drupal\Core\DependencyInjection\ContainerInjectionInterface', class_implements($class))) {
        $controller = $class::create($this->container);
      }
      else {
        $controller = new $class();
      }

      $controller
        ->setTranslationManager($this->translationManager)
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
      $definition = $this->getDefinition($entity_type, TRUE);
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
      if (method_exists($controller, 'setTranslationManager')) {
        $controller->setTranslationManager($this->translationManager);
      }
      $this->controllers[$controller_type][$entity_type] = $controller;
    }
    return $this->controllers[$controller_type][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminRouteInfo($entity_type_id, $bundle) {
    if (($entity_type = $this->getDefinition($entity_type_id)) && $admin_form = $entity_type->getLinkTemplate('admin-form')) {
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
      if ($cache = $this->cache->get($cid)) {
        $this->baseFieldDefinitions[$entity_type_id] = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $this->baseFieldDefinitions[$entity_type_id] = $this->buildBaseFieldDefinitions($entity_type_id);
        $this->cache->set($cid, $this->baseFieldDefinitions[$entity_type_id], Cache::PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
       }
     }
    return $this->baseFieldDefinitions[$entity_type_id];
  }

  /**
   * Builds base field definitions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\ContentEntityInterface are supported
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name.
   *
   * @throws \LogicException
   *   Thrown if one of the entity keys is flagged as translatable.
   */
  protected function buildBaseFieldDefinitions($entity_type_id) {
    $entity_type = $this->getDefinition($entity_type_id);
    $class = $entity_type->getClass();

    $base_field_definitions = $class::baseFieldDefinitions($entity_type);

    // Invoke hook.
    $result = $this->moduleHandler->invokeAll('entity_base_field_info', array($entity_type));
    $base_field_definitions = NestedArray::mergeDeep($base_field_definitions, $result);

    // Automatically set the field name for non-configurable fields.
    foreach ($base_field_definitions as $field_name => $base_field_definition) {
      if ($base_field_definition instanceof FieldDefinition) {
        $base_field_definition->setName($field_name);
        $base_field_definition->setTargetEntityTypeId($entity_type_id);
      }
    }

    // Invoke alter hook.
    $this->moduleHandler->alter('entity_base_field_info', $base_field_definitions, $entity_type);

    // Ensure all basic fields are not defined as translatable.
    $keys = array_intersect_key(array_filter($entity_type->getKeys()), array_flip(array('id', 'revision', 'uuid', 'bundle')));
    $untranslatable_fields = array_flip(array('langcode') + $keys);
    foreach ($base_field_definitions as $field_name => $definition) {
      if (isset($untranslatable_fields[$field_name]) && $definition->isTranslatable()) {
        throw new \LogicException(String::format('The @field field cannot be translatable.', array('@field' => $definition->getLabel())));
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
      if ($cache = $this->cache->get($cid)) {
        $bundle_field_definitions = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $bundle_field_definitions = $this->buildBundleFieldDefinitions($entity_type_id, $bundle, $base_field_definitions);
        $this->cache->set($cid, $bundle_field_definitions, Cache::PERMANENT, array('entity_types' => TRUE, 'entity_field_info' => TRUE));
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

    // Invoke 'per bundle' hook.
    $result = $this->moduleHandler->invokeAll('entity_bundle_field_info', array($entity_type, $bundle, $base_field_definitions));
    $bundle_field_definitions = NestedArray::mergeDeep($bundle_field_definitions, $result);

    // Automatically set the field name for non-configurable fields.
    foreach ($bundle_field_definitions as $field_name => $field_definition) {
      if ($field_definition instanceof FieldDefinition) {
        $field_definition->setName($field_name);
        $field_definition->setTargetEntityTypeId($entity_type_id);
      }
    }

    // Invoke 'per bundle' alter hook.
    $this->moduleHandler->alter('entity_bundle_field_info', $bundle_field_definitions, $entity_type, $bundle);

    return $bundle_field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedFieldDefinitions() {
    $this->baseFieldDefinitions = array();
    $this->fieldDefinitions = array();
    Cache::deleteTags(array('entity_field_info' => TRUE));
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
    if (!isset($this->bundleInfo)) {
      $langcode = $this->languageManager->getCurrentLanguage()->id;
      if ($cache = $this->cache->get("entity_bundle_info:$langcode")) {
        $this->bundleInfo = $cache->data;
      }
      else {
        $this->bundleInfo = $this->moduleHandler->invokeAll('entity_bundle_info');
        // If no bundles are provided, use the entity type name and label.
        foreach ($this->getDefinitions() as $type => $entity_type) {
          if (!isset($this->bundleInfo[$type])) {
            $this->bundleInfo[$type][$type]['label'] = $entity_type->getLabel();
          }
        }
        $this->moduleHandler->alter('entity_bundle_info', $this->bundleInfo);
        $this->cache->set("entity_bundle_info:$langcode", $this->bundleInfo, Cache::PERMANENT, array('entity_types' => TRUE));
      }
    }

    return $this->bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $options = array();
    foreach ($this->getDefinitions() as $entity_type => $definition) {
      $options[$entity_type] = $definition->getLabel();
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
        $langcode = $this->languageManager->getCurrentLanguage(Language::TYPE_CONTENT)->id;
      }

      // Retrieve language fallback candidates to perform the entity language
      // negotiation.
      $context['data'] = $entity;
      $context += array('operation' => 'entity_view');
      $candidates = $this->languageManager->getFallbackCandidates($langcode, $context);

      // Ensure the default language has the proper language code.
      $default_language = $entity->getUntranslated()->language();
      $candidates[$default_language->id] = Language::LANGCODE_DEFAULT;

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

}
