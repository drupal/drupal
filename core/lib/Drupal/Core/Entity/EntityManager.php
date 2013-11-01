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
 * \Drupal\Core\Entity\EntityManagerInterface::defaults.
 *
 * @see \Drupal\Core\Entity\Annotation\EntityType
 * @see \Drupal\Core\Entity\EntityInterface
 * @see entity_get_info()
 * @see hook_entity_info_alter()
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
    // Allow the plugin definition to be altered by hook_entity_info_alter().

    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
    $this->languageManager = $language_manager;
    $this->namespaces = $namespaces;
    $this->translationManager = $translation_manager;

    $this->discovery = new AnnotatedClassDiscovery('Entity', $namespaces, 'Drupal\Core\Entity\Annotation\EntityType');
    $this->discovery = new InfoHookDecorator($this->discovery, 'entity_info');
    $this->discovery = new AlterDecorator($this->discovery, 'entity_info');
    $this->discovery = new CacheDecorator($this->discovery, 'entity_info:' . $this->languageManager->getLanguage(Language::TYPE_INTERFACE)->id, 'cache', CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
    $this->factory = new DefaultFactory($this->discovery);
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
  public function hasController($entity_type, $controller_type) {
    $definition = $this->getDefinition($entity_type);
    return !empty($definition['controllers'][$controller_type]);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getStorageController($entity_type) {
    return $this->getController($entity_type, 'storage');
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type) {
    return $this->getController($entity_type, 'view_builder');
  }

  /**
   * {@inheritdoc}
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
        $this->controllers[$controller_type][$entity_type] = new $class($entity_type, $this->getDefinition($entity_type));
      }
    }
    return $this->controllers[$controller_type][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state = array()) {
    $form_state += entity_form_state_defaults($entity, $operation);
    $form_id = $form_state['build_info']['callback_object']->getFormId();
    return drupal_build_form($form_id, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPath($entity_type, $bundle) {
    $admin_path = '';
    $entity_info = $this->getDefinition($entity_type);
    // Check for an entity type's admin base path.
    if (isset($entity_info['route_base_path'])) {
      // Replace any dynamic 'bundle' portion of the path with the actual bundle.
      $admin_path = str_replace('{bundle}', $bundle, $entity_info['route_base_path']);
    }

    return $admin_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminRouteInfo($entity_type, $bundle) {
    return array(
      'route_name' => "field_ui.overview_$entity_type",
      'route_parameters' => array(
        'bundle' => $bundle,
      )
    );
  }

  /**
   * {@inheritdoc}
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

        $base_definitions = $class::baseFieldDefinitions($entity_type);
        foreach ($base_definitions as &$base_definition) {
          // Support old-style field types to avoid that all base field
          // definitions need to be changed.
          // @todo: Remove after https://drupal.org/node/2047229.
          $base_definition['type'] = preg_replace('/(.+)_field/', 'field_item:$1', $base_definition['type']);
        }
        $this->entityFieldInfo[$entity_type] = array(
          'definitions' => $base_definitions,
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

        // Enforce fields to be multiple and untranslatable by default.
        $entity_info = $this->getDefinition($entity_type);
        $keys = array_intersect_key(array_filter($entity_info['entity_keys']), array_flip(array('id', 'revision', 'uuid', 'bundle')));
        $untranslatable_fields = array_flip(array('langcode') + $keys);
        foreach (array('definitions', 'optional') as $key) {
          foreach ($this->entityFieldInfo[$entity_type][$key] as $name => &$definition) {
            $definition['list'] = TRUE;
            // Ensure ids and langcode fields are never made translatable.
            if (isset($untranslatable_fields[$name]) && !empty($definition['translatable'])) {
              throw new \LogicException(format_string('The @field field cannot be translatable.', array('@field' => $definition['label'])));
            }
            if (!isset($definition['translatable'])) {
              $definition['translatable'] = FALSE;
            }
          }
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
   * {@inheritdoc}
   */
  public function getFieldDefinitionsByConstraints($entity_type, array $constraints) {
    // @todo: Add support for specifying multiple bundles.
    return $this->getFieldDefinitions($entity_type, isset($constraints['Bundle']) ? $constraints['Bundle'] : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedFieldDefinitions() {
    unset($this->entityFieldInfo);
    unset($this->fieldDefinitions);
    $this->cache->deleteTags(array('entity_field_info' => TRUE));
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

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $options = array();
    foreach ($this->getDefinitions() as $entity_type => $definition) {
      $options[$entity_type] = $definition['label'];
    }

    return $options;
  }

}
