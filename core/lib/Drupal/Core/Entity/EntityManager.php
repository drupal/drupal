<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;
use Drupal\Core\Cache\CacheBackendInterface;
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
   * Constructs a new Entity plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   */
  public function __construct(\Traversable $namespaces, ContainerInterface $container) {
    // Allow the plugin definition to be altered by hook_entity_info_alter().
    $annotation_namespaces = array(
      'Drupal\Core\Entity\Annotation' => DRUPAL_ROOT . '/core/lib',
    );
    $this->discovery = new AnnotatedClassDiscovery('Core/Entity', $namespaces, $annotation_namespaces, 'Drupal\Core\Entity\Annotation\EntityType');
    $this->discovery = new InfoHookDecorator($this->discovery, 'entity_info');
    $this->discovery = new AlterDecorator($this->discovery, 'entity_info');
    $this->discovery = new CacheDecorator($this->discovery, 'entity_info:' . language(Language::TYPE_INTERFACE)->langcode, 'cache', CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));

    $this->factory = new DefaultFactory($this->discovery);
    $this->container = $container;
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
    $definition = $definition['controllers'];
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
      $class = $this->getControllerClass($entity_type, 'storage');
      if (in_array('Drupal\Core\Entity\EntityControllerInterface', class_implements($class))) {
        $this->controllers['storage'][$entity_type] = $class::createInstance($this->container, $entity_type, $this->getDefinition($entity_type));
      }
      else {
        $this->controllers['storage'][$entity_type] = new $class($entity_type);
      }
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
      if (in_array('Drupal\Core\Entity\EntityControllerInterface', class_implements($class))) {
        $this->controllers['form'][$operation][$entity_type] = $class::createInstance($this->container, $entity_type, $this->getDefinition($entity_type));
      }
      else {
        $this->controllers['form'][$operation][$entity_type] = new $class($operation);
      }
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
      $class = $this->getControllerClass($entity_type, 'render');
      if (in_array('Drupal\Core\Entity\EntityControllerInterface', class_implements($class))) {
        $this->controllers['render'][$entity_type] = $class::createInstance($this->container, $entity_type, $this->getDefinition($entity_type));
      }
      else {
        $this->controllers['render'][$entity_type] = new $class($entity_type);
      }
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
      $class = $this->getControllerClass($entity_type, 'access');
      if (in_array('Drupal\Core\Entity\EntityControllerInterface', class_implements($class))) {
        $this->controllers['access'][$entity_type] = $class::createInstance($this->container, $entity_type, $this->getDefinition($entity_type));
      }
      else {
        $this->controllers['access'][$entity_type] = new $class($entity_type);
      }
    }
    return $this->controllers['access'][$entity_type];
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

}
