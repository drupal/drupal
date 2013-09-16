<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages entity type plugin definitions.
 *
 * Each entity type definition array is set in the entity type's
 * annotation and altered by hook_entity_info_alter().
 *
 * The defaults for the plugin definition are provided in
 * \Drupal\Core\Entity\EntityManager::defaults.
 *
 * @see \Drupal\Core\Entity\Annotation\EntityType
 * @see \Drupal\Core\Entity\EntityInterface
 * @see entity_get_info()
 * @see hook_entity_info_alter()
 */
class EntityManager extends PluginManagerBase {

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
   * An array of field information per entity type, i.e. containing definitions.
   *
   * @var array
   *
   * @see hook_entity_field_info()
   */
  protected $entityFieldInfo;

  /**
   * Static cache of field definitions per bundle and entity type.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * The root paths.
   *
   * @see \Drupal\Core\Entity\EntityManager::__construct().
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

  /*
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
    // Allow the plugin definition to be altered by hook_entity_info_alter().

    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
    $this->languageManager = $language_manager;
    $this->namespaces = $namespaces;
    $this->translationManager = $translation_manager;

    $this->doDiscovery($namespaces);
    $this->factory = new DefaultFactory($this->discovery);
    $this->container = $container;
  }

  protected function doDiscovery($namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Entity', $namespaces, 'Drupal\Core\Entity\Annotation\EntityType');
    $this->discovery = new InfoHookDecorator($this->discovery, 'entity_info');
    $this->discovery = new AlterDecorator($this->discovery, 'entity_info');
    $this->discovery = new CacheDecorator($this->discovery, 'entity_info:' . $this->languageManager->getLanguage(Language::TYPE_INTERFACE)->id, 'cache', CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
  }

  /**
   * Add more namespaces to the entity manager.
   *
   * This is usually only necessary for uninstall purposes.
   *
   * @todo Remove this method, along with doDiscovery(), when
   * https://drupal.org/node/1199946 is fixed.
   *
   * @param \Traversable $namespaces
   *
   * @see comment_uninstall()
   */
  public function addNamespaces(\Traversable $namespaces) {
    reset($this->namespaces);
    $iterator = new \AppendIterator;
    $iterator->append(new \IteratorIterator($this->namespaces));
    $iterator->append($namespaces);
    $this->doDiscovery($iterator);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();

    $this->bundleInfo = NULL;
  }


  /**
   * Checks whether a certain entity type has a certain controller.
   *
   * @param string $entity_type
   *   The name of the entity type.
   * @param string $controller_type
   *   The name of the controller.
   *
   * @return bool
   *   Returns TRUE if the entity type has the controller, else FALSE.
   */
  public function hasController($entity_type, $controller_type) {
    $definition = $this->getDefinition($entity_type);
    return !empty($definition['controllers'][$controller_type]);
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
    if (!$definition) {
      throw new \InvalidArgumentException(sprintf('The %s entity type does not exist.', $entity_type));
    }
    $definition = $definition['controllers'];
    if (!$definition) {
      throw new \InvalidArgumentException(sprintf('The entity type (%s) does not exist.', $entity_type));
    }

    if (empty($definition[$controller_type])) {
      throw new \InvalidArgumentException(sprintf('The entity type (%s) did not specify a %s controller.', $entity_type, $controller_type));
    }

    $class = $definition[$controller_type];

    // Some class definitions can be nested.
    if (isset($nested)) {
      if (empty($class[$nested])) {
        throw new \InvalidArgumentException(sprintf("The entity type (%s) did not specify a %s controller: %s.", $entity_type, $controller_type, $nested));
      }

      $class = $class[$nested];
    }

    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('The entity type (%s) %s controller "%s" does not exist.', $entity_type, $controller_type, $class));
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
    return $this->getController($entity_type, 'storage');
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
      $class = $this->getControllerClass($entity_type, 'list');
      if (in_array('Drupal\Core\Entity\EntityControllerInterface', class_implements($class))) {
        $this->controllers['listing'][$entity_type] = $class::createInstance($this->container, $entity_type, $this->getDefinition($entity_type));
      }
      else {
        $this->controllers['listing'][$entity_type] = new $class($entity_type, $this->getStorageController($entity_type));
      }
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
      $class = $this->getControllerClass($entity_type, 'form', $operation);
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
   * Creates a new render controller instance.
   *
   * @param string $entity_type
   *   The entity type for this render controller.
   *
   * @return \Drupal\Core\Entity\EntityRenderControllerInterface.
   *   A render controller instance.
   */
  public function getRenderController($entity_type) {
    return $this->getController($entity_type, 'render');
  }

  /**
   * Creates a new access controller instance.
   *
   * @param string $entity_type
   *   The entity type for this access controller.
   *
   * @return \Drupal\Core\Entity\EntityAccessControllerInterface.
   *   A access controller instance.
   */
  public function getAccessController($entity_type) {
    if (!isset($this->controllers['access'][$entity_type])) {
      $controller = $this->getController($entity_type, 'access');
      $controller->setModuleHandler($this->moduleHandler);
    }
    return $this->controllers['access'][$entity_type];
  }

  /**
   * Creates a new controller instance.
   *
   * @param string $entity_type
   *   The entity type for this access controller.
   * @param string $controller_type
   *   The controller type to create an instance for.
   *
   * @return mixed.
   *   A controller instance.
   */
  protected function getController($entity_type, $controller_type) {
    if (!isset($this->controllers[$controller_type][$entity_type])) {
      $class = $this->getControllerClass($entity_type, $controller_type);
      if (in_array('Drupal\Core\Entity\EntityControllerInterface', class_implements($class))) {
        $this->controllers[$controller_type][$entity_type] = $class::createInstance($this->container, $entity_type, $this->getDefinition($entity_type));
      }
      else {
        $this->controllers[$controller_type][$entity_type] = new $class($entity_type);
      }
    }
    return $this->controllers[$controller_type][$entity_type];
  }

  /**
   * Returns the built and processed entity form for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be created or edited.
   * @param string $operation
   *   (optional) The operation identifying the form variation to be returned.
   *   Defaults to 'default'.
   * @param array $form_state
   *   (optional) An associative array containing the current state of the form.
   *   Use this to pass additional information to the form, such as the
   *   langcode. Defaults to an empty array.
   * @code
   *   $form_state['langcode'] = $langcode;
   *   $manager = \Drupal::entityManager();
   *   $form = $manager->getForm($entity, 'default', $form_state);
   * @endcode
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state = array()) {
    $form_state += entity_form_state_defaults($entity, $operation);
    $form_id = $form_state['build_info']['callback_object']->getFormID();
    return drupal_build_form($form_id, $form_state);
  }

  /**
   * Returns the administration path for an entity type's bundle.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string
   *   The administration path for an entity type bundle, if it exists.
   */
  public function getAdminPath($entity_type, $bundle) {
    $admin_path = '';
    $entity_info = $this->getDefinition($entity_type);
    // Check for an entity type's admin base path.
    if (isset($entity_info['route_base_path'])) {
      // If the entity type has a bundle prefix, strip it out of the path.
      if (isset($entity_info['bundle_prefix'])) {
        $bundle = str_replace($entity_info['bundle_prefix'], '', $bundle);
      }
      // Replace any dynamic 'bundle' portion of the path with the actual bundle.
      $admin_path = str_replace('{bundle}', $bundle, $entity_info['route_base_path']);
    }

    return $admin_path;
  }

  /**
   * Returns the route information for an entity type's bundle.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An associative array with the following keys:
   *   - route_name: The name of the route.
   *   - route_parameters: (optional) An associative array of parameter names
   *     and values.
   */
  public function getAdminRouteInfo($entity_type, $bundle) {
    $entity_info = $this->getDefinition($entity_type);
    if (isset($entity_info['bundle_prefix'])) {
      $bundle = str_replace($entity_info['bundle_prefix'], '', $bundle);
    }
    return array(
      'route_name' => "field_ui.overview_$entity_type",
      'route_parameters' => array(
        'bundle' => $bundle,
      )
    );
  }

  /**
   * Gets an array of entity field definitions.
   *
   * If a bundle is passed, fields specific to this bundle are included. Entity
   * fields are always multi-valued, so 'list' is TRUE for each returned field
   * definition.
   *
   * @param string $entity_type
   *   The entity type to get field definitions for.
   * @param string $bundle
   *   (optional) The entity bundle for which to get field definitions. If NULL
   *   is passed, no bundle-specific fields are included. Defaults to NULL.
   *
   * @return array
   *   An array of field definitions of entity fields, keyed by field
   *   name. In addition to the typed data definition keys as described at
   *   \Drupal\Core\TypedData\TypedDataManager::create() the following keys are
   *   supported:
   *   - queryable: Whether the field is queryable via QueryInterface.
   *     Defaults to TRUE if 'computed' is FALSE or not set, to FALSE otherwise.
   *   - translatable: Whether the field is translatable. Defaults to FALSE.
   *   - configurable: A boolean indicating whether the field is configurable
   *     via field.module. Defaults to FALSE.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   * @see \Drupal\Core\Entity\EntityManager::getFieldDefinitionsByConstraints()
   */
  public function getFieldDefinitions($entity_type, $bundle = NULL) {
    if (!isset($this->entityFieldInfo[$entity_type])) {
      // First, try to load from cache.
      $cid = 'entity_field_definitions:' . $entity_type . ':' . $this->languageManager->getLanguage(Language::TYPE_INTERFACE)->id;
      if ($cache = $this->cache->get($cid)) {
        $this->entityFieldInfo[$entity_type] = $cache->data;
      }
      else {
        $class = $this->factory->getPluginClass($entity_type, $this->getDefinition($entity_type));
        $this->entityFieldInfo[$entity_type] = array(
          'definitions' => $class::baseFieldDefinitions($entity_type),
          // Contains definitions of optional (per-bundle) fields.
          'optional' => array(),
          // An array keyed by bundle name containing the optional fields added
          // by the bundle.
          'bundle map' => array(),
        );

        // Invoke hooks.
        $result = $this->moduleHandler->invokeAll($entity_type . '_field_info');
        $this->entityFieldInfo[$entity_type] = NestedArray::mergeDeep($this->entityFieldInfo[$entity_type], $result);
        $result = $this->moduleHandler->invokeAll('entity_field_info', array($entity_type));
        $this->entityFieldInfo[$entity_type] = NestedArray::mergeDeep($this->entityFieldInfo[$entity_type], $result);

        $hooks = array('entity_field_info', $entity_type . '_field_info');
        $this->moduleHandler->alter($hooks, $this->entityFieldInfo[$entity_type], $entity_type);

        // Enforce fields to be multiple by default.
        foreach ($this->entityFieldInfo[$entity_type]['definitions'] as &$definition) {
          $definition['list'] = TRUE;
        }
        foreach ($this->entityFieldInfo[$entity_type]['optional'] as &$definition) {
          $definition['list'] = TRUE;
        }
        $this->cache->set($cid, $this->entityFieldInfo[$entity_type], CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE, 'entity_field_info' => TRUE));
      }
    }

    if (!$bundle) {
      return $this->entityFieldInfo[$entity_type]['definitions'];
    }
    else {
      // Add in per-bundle fields.
      if (!isset($this->fieldDefinitions[$entity_type][$bundle])) {
        $this->fieldDefinitions[$entity_type][$bundle] = $this->entityFieldInfo[$entity_type]['definitions'];
        if (isset($this->entityFieldInfo[$entity_type]['bundle map'][$bundle])) {
          $this->fieldDefinitions[$entity_type][$bundle] += array_intersect_key($this->entityFieldInfo[$entity_type]['optional'], array_flip($this->entityFieldInfo[$entity_type]['bundle map'][$bundle]));
        }
      }
      return $this->fieldDefinitions[$entity_type][$bundle];
    }
  }

  /**
   * Gets an array of entity field definitions based on validation constraints.
   *
   * @param string $entity_type
   *   The entity type to get field definitions for.
   * @param array $constraints
   *   An array of entity constraints as used for entities in typed data
   *   definitions, i.e. an array optionally including a 'Bundle' key.
   *   For example the constraints used by an entity reference could be:
   *   @code
   *   array(
   *     'Bundle' => 'article',
   *   )
   *   @endcode
   *
   * @return array
   *   An array of field definitions of entity fields, keyed by field
   *   name.
   *
   * @see \Drupal\Core\Entity\EntityManager::getFieldDefinitions()
   */
  public function getFieldDefinitionsByConstraints($entity_type, array $constraints) {
    // @todo: Add support for specifying multiple bundles.
    return $this->getFieldDefinitions($entity_type, isset($constraints['Bundle']) ? $constraints['Bundle'] : NULL);
  }

  /**
   * Clears static and persistent field definition caches.
   */
  public function clearCachedFieldDefinitions() {
    unset($this->entityFieldInfo);
    unset($this->fieldDefinitions);
    $this->cache->deleteTags(array('entity_field_info' => TRUE));
  }

  /**
   * Get the bundle info of an entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Returns the bundle information for the specified entity type.
   */
  public function getBundleInfo($entity_type) {
    $bundle_info = $this->getAllBundleInfo();
    return isset($bundle_info[$entity_type]) ? $bundle_info[$entity_type] : array();
  }

  /**
   * Get the bundle info of all entity types.
   *
   * @return array
   *   An array of all bundle information.
   */
  public function getAllBundleInfo() {
    if (!isset($this->bundleInfo)) {
      $langcode = $this->languageManager->getLanguage(Language::TYPE_INTERFACE)->id;
      if ($cache = $this->cache->get("entity_bundle_info:$langcode")) {
        $this->bundleInfo = $cache->data;
      }
      else {
        $this->bundleInfo = $this->moduleHandler->invokeAll('entity_bundle_info');
        // If no bundles are provided, use the entity type name and label.
        foreach ($this->getDefinitions() as $type => $entity_info) {
          if (!isset($this->bundleInfo[$type])) {
            $this->bundleInfo[$type][$type]['label'] = $entity_info['label'];
          }
        }
        $this->moduleHandler->alter('entity_bundle_info', $this->bundleInfo);
        $this->cache->set("entity_bundle_info:$langcode", $this->bundleInfo, CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
      }
    }

    return $this->bundleInfo;
  }

}
