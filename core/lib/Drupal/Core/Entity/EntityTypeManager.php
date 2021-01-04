<?php

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\Exception\InvalidLinkTemplateException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Manages entity type plugin definitions.
 *
 * Each entity type definition array is set in the entity type's annotation and
 * altered by hook_entity_type_alter().
 *
 * Do not use hook_entity_type_alter() hook to add information to entity types,
 * unless one of the following is true:
 * - You are filling in default values.
 * - You need to dynamically add information only in certain circumstances.
 * - Your hook needs to run after hook_entity_type_build() implementations.
 * Use hook_entity_type_build() instead in all other cases.
 *
 * @see \Drupal\Core\Entity\Annotation\EntityType
 * @see \Drupal\Core\Entity\EntityInterface
 * @see \Drupal\Core\Entity\EntityTypeInterface
 * @see hook_entity_type_alter()
 * @see hook_entity_type_build()
 */
class EntityTypeManager extends DefaultPluginManager implements EntityTypeManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Contains instantiated handlers keyed by handler type and entity type.
   *
   * @var array
   */
  protected $handlers = [];

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The entity last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

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
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   The entity last installed schema repository.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, TranslationInterface $string_translation, ClassResolverInterface $class_resolver, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository) {
    parent::__construct('Entity', $namespaces, $module_handler, 'Drupal\Core\Entity\EntityInterface');

    $this->setCacheBackend($cache, 'entity_type', ['entity_types']);
    $this->alterInfo('entity_type');

    $this->discovery = new AnnotatedClassDiscovery('Entity', $namespaces, 'Drupal\Core\Entity\Annotation\EntityType');
    $this->stringTranslation = $string_translation;
    $this->classResolver = $class_resolver;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
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
      $function = $module . '_entity_type_build';
      $function($definitions);
    }
    foreach ($definitions as $plugin_id => $definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);

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
   * Gets the active definition for a content entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The active entity type definition.
   *
   * @internal
   */
  public function getActiveDefinition($entity_type_id) {
    $definition = $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type_id);
    return $definition ?: $this->getDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->handlers = [];
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    parent::useCaches($use_caches);
    if (!$use_caches) {
      $this->handlers = [];
      $this->container->get('entity.memory_cache')->reset();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandler($entity_type_id, $handler_type) {
    if ($definition = $this->getDefinition($entity_type_id, FALSE)) {
      return $definition->hasHandlerClass($handler_type);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type_id) {
    return $this->getHandler($entity_type_id, 'storage');
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilder($entity_type_id) {
    return $this->getHandler($entity_type_id, 'list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_type_id, $operation) {
    if (!$class = $this->getDefinition($entity_type_id, TRUE)->getFormClass($operation)) {
      throw new InvalidPluginDefinitionException($entity_type_id, sprintf('The "%s" entity type did not specify a "%s" form class.', $entity_type_id, $operation));
    }

    $form_object = $this->classResolver->getInstanceFromDefinition($class);

    return $form_object
      ->setStringTranslation($this->stringTranslation)
      ->setModuleHandler($this->moduleHandler)
      ->setEntityTypeManager($this)
      ->setOperation($operation);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteProviders($entity_type_id) {
    if (!isset($this->handlers['route_provider'][$entity_type_id])) {
      $route_provider_classes = $this->getDefinition($entity_type_id, TRUE)->getRouteProviderClasses();

      foreach ($route_provider_classes as $type => $class) {
        $this->handlers['route_provider'][$entity_type_id][$type] = $this->createHandlerInstance($class, $this->getDefinition($entity_type_id));
      }
    }

    return isset($this->handlers['route_provider'][$entity_type_id]) ? $this->handlers['route_provider'][$entity_type_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type_id) {
    return $this->getHandler($entity_type_id, 'view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlHandler($entity_type_id) {
    return $this->getHandler($entity_type_id, 'access');
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($entity_type_id, $handler_type) {
    if (!isset($this->handlers[$handler_type][$entity_type_id])) {
      $definition = $this->getDefinition($entity_type_id);
      $class = $definition->getHandlerClass($handler_type);
      if (!$class) {
        throw new InvalidPluginDefinitionException($entity_type_id, sprintf('The "%s" entity type did not specify a %s handler.', $entity_type_id, $handler_type));
      }
      $this->handlers[$handler_type][$entity_type_id] = $this->createHandlerInstance($class, $definition);
    }

    return $this->handlers[$handler_type][$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = NULL) {
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
      $handler->setStringTranslation($this->stringTranslation);
    }

    return $handler;
  }

}
